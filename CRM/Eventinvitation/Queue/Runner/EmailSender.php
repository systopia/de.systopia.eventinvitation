<?php

/*-------------------------------------------------------+
| SYSTOPIA Event Invitation                              |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Zschiedrich (zschiedrich@systopia.de)       |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

use CRM_Eventinvitation_ExtensionUtil as E;

class CRM_Eventinvitation_Queue_Runner_EmailSender
{
    /** @var string $title Will be set as title by the runner. */
    public $title;

    /** @var CRM_Eventinvitation_Object_RunnerData $runnerData */
    private $runnerData;

    /** @var string $emailSender */
    private $emailSender;

    public function __construct(
        CRM_Eventinvitation_Object_RunnerData $runnerData,
        string $emailSender,
        int $offset
    ) {
        $this->runnerData = $runnerData;
        $this->emailSender = $emailSender;

        $start = $offset + 1;
        $end = $offset + count($runnerData->contactIds);

        $this->title = E::ts('Sending e-mails %1 to %2.', [1 => $start, 2 => $end]);
    }

    public function run(): bool
    {
        foreach ($this->runnerData->contactIds as $contactId) {
            try {
                $participantId = $this->setParticipantToInvited($contactId);
                $templateTokens = $this->setTemplateTokens($participantId);
                $this->sendEmail($contactId, $templateTokens);
            } catch (Exception $error) {
                // FIXME: What to do with errors?
            }
        }

        return true;
    }

    private function setParticipantToInvited(string $contactId): int
    {
        $queryResult = civicrm_api3(
            'Participant',
            'create',
            [
                'event_id' => $this->runnerData->eventId,
                'contact_id' => $contactId,
                'status_id' => CRM_Eventinvitation_Upgrader::PARTICIPANT_STATUS_INVITED_NAME,
                'role_id' => $this->runnerData->participantRoleId,
            ]
        );

        return $queryResult['id'];
    }

    private function setTemplateTokens(int $participantId): array
    {
        $invitationCode = CRM_Eventinvitation_EventInvitationCode::generate($participantId);

        $settings = Civi::settings()->get(CRM_Eventinvitation_Form_Settings::SETTINGS_KEY);

        $link = '';

        if (
            is_array($settings)
            && array_key_exists(CRM_Eventinvitation_Form_Settings::LINK_TARGET_IS_CUSTOM_FORM_NAME, $settings)
            && array_key_exists(CRM_Eventinvitation_Form_Settings::CUSTOM_LINK_TARGET_FORM_NAME, $settings)
        ) {
            $link = $settings[CRM_Eventinvitation_Form_Settings::CUSTOM_LINK_TARGET_FORM_NAME];

            $link .= '?code=' + $invitationCode; // TODO: This is reaaally ugly and must be standardised.
        } else {
            $path = 'civicrm/eventinvitation/register'; // NOTE: This must be adjusted if the URL in the menu XML is ever changed.

            $link = CRM_Utils_System::url($path, ['code' => $invitationCode], true, null);
        }

        $templateTokens = [
            CRM_Eventinvitation_Form_Task_ContactSearch::TEMPLATE_CODE_TOKEN => $link,
        ];

        return $templateTokens;
    }

    private function sendEmail(string $contactId, array $templateTokens): void
    {
        $contactData = civicrm_api3(
            'Contact',
            'getsingle',
            [
                'id' => $contactId,
                'return' => 'display_name,email'
            ]
        );

        $emailData = [
            'id' => $this->runnerData->templateId,
            'toName' => $contactData['display_name'],
            'toEmail' => $contactData['email'],
            'from' => $this->emailSender,
            'contactId' => $contactId,
            'tplParams' => $templateTokens,
        ];

        civicrm_api3('MessageTemplate', 'send', $emailData);
    }
}
