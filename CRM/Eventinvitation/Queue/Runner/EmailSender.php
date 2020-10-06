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
                $this->setParticipantToInvited($contactId);
                $this->sendEmail($contactId);
            } catch (Exception $error) {
                // FIXME: What to do with errors?
            }
        }

        return true;
    }

    private function setParticipantToInvited(string $contactId): void
    {
        civicrm_api3(
            'Participant',
            'create',
            [
                'event_id' => $this->runnerData->eventId,
                'contact_id' => $contactId,
                'status_id' => CRM_Eventinvitation_Upgrader::PARTICIPANT_STATUS_INVITED_NAME,
                'role_id' => $this->runnerData->participantRoleId,
            ]
        );
    }

    private function sendEmail(string $contactId): void
    {
        $contactData = civicrm_api3(
            'Contact',
            'getsingle',
            [
                'id' => $contactId,
            ]
        );

        $emailData = [
            'id' => $this->runnerData->templateId,
            'toName' => $contactData['display_name'],
            'toEmail' => $contactData['email'],
            'from' => $this->emailSender,
            'contactId' => $contactId,
            'tplParams' => $this->runnerData->templateTokens,
        ];

        civicrm_api3('MessageTemplate', 'send', $emailData);
    }
}
