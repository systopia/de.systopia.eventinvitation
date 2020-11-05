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
use chillerlan\QRCode\QRCode;


abstract class CRM_Eventinvitation_Queue_Runner_Job
{
    /** @var string $title Will be set as title by the runner. */
    public $title;

    /** @var CRM_Eventinvitation_Object_RunnerData $runnerData */
    protected $runnerData;

    public function __construct(
        CRM_Eventinvitation_Object_RunnerData $runnerData,
        int $offset
    ) {
        $this->runnerData = $runnerData;

        $start = $offset + 1;
        $end = $offset + count($runnerData->contactIds);
        $this->title = E::ts('Processing contacts %1 to %2.', [1 => $start, 2 => $end]);
    }

    /**
     * This part will be implemented by the specific runners
     *
     * @param integer $contactId
     *   contact ID
     * @param array $templateTokens
     *   tokens
     *
     * @throws \CiviCRM_API3_Exception
     */
    protected abstract function processContact($contactId, $templateTokens);

    /**
     * Dispatch the contacts to the processContact function
     *
     * @return true
     */
    public function run(): bool
    {
        foreach ($this->runnerData->contactIds as $contactId) {
            $transaction = new CRM_Core_Transaction();

            try {
                $participantId = $this->setParticipantToInvited($contactId);
                $templateTokens = $this->getTemplateTokens($participantId);
                $this->processContact($contactId, $templateTokens);
            } catch (Exception $error) {
                $transaction->rollback();
                Civi::log()->warning("Generating email/pdf for contact {$contactId} failed: " . $error->getMessage());
            }

            $transaction->commit();
        }

        return true;
    }

    /**
     * Mark the contact to be 'Invited'.
     * Note that;
     *  - they might already be invited - in which case we do nothing
     *  - they might have already rejected, accepted, e.g. - in which case we also do nothing
     *
     * As a result: if there is already an existing participant for this contact/event, we do nothing.
     * @todo: do we want to upgrade an exisiting invitation, e.g. the date?
     *
     * @param string $contactId
     *   the contact that should be invited
     *
     * @return int
     * @throws \CiviCRM_API3_Exception
     */
    protected function setParticipantToInvited(string $contactId): int
    {
        // check if there is/are already existing participants
        $existing_participant = civicrm_api3(
            'Participant',
            'get',
            [
                'event_id' => $this->runnerData->eventId,
                'contact_id' => $contactId,
                'option.limit' => 1,
            ]
        );

        if (!empty($existing_participant['id'])) {
            // there is one, use that!
            return $existing_participant['id'];

        } else {
            // if there isn't one: create
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
    }

    /**
     * Generate
     * @param int $participantId
     *
     * @return array
     */
    protected function getTemplateTokens(int $participantId): array
    {
        // collect some tokens for the
        $templateTokens = [];

        // get the invitation link
        $invitationCode = CRM_Eventinvitation_EventInvitationCode::generate($participantId);

        $settings = Civi::settings()->get(CRM_Eventinvitation_Form_Settings::SETTINGS_KEY);

        $link = '';
        if (
            is_array($settings)
            && array_key_exists(CRM_Eventinvitation_Form_Settings::LINK_TARGET_IS_CUSTOM_FORM_NAME, $settings)
            && array_key_exists(CRM_Eventinvitation_Form_Settings::CUSTOM_LINK_TARGET_FORM_NAME, $settings)
        ) {
            // get the link
            $link = $settings[CRM_Eventinvitation_Form_Settings::CUSTOM_LINK_TARGET_FORM_NAME];

            // replace the code token
            $link = preg_replace('/\{token\}/', $invitationCode, $link);

        } else {
            $path = 'civicrm/eventinvitation/register'; // NOTE: This must be adjusted if the URL in the menu XML is ever changed.

            $link = CRM_Utils_System::url($path, ['code' => $invitationCode], true, null);
        }
        $templateTokens[CRM_Eventinvitation_Form_Task_ContactSearch::TEMPLATE_CODE_TOKEN] = $link;

        // add a QR code
        if ($link) {
            try {
                $qr_code = new QRCode();
                $qr_code_data = $qr_code->render($link);
                $templateTokens[CRM_Eventinvitation_Form_Task_ContactSearch::TEMPLATE_CODE_TOKEN_QR_DATA] = $qr_code_data;
                $qr_code_alt_text = E::ts("Registration QR Code");
                $templateTokens[CRM_Eventinvitation_Form_Task_ContactSearch::TEMPLATE_CODE_TOKEN_QR_IMG] = "<img alt=\"{$qr_code_alt_text}\" src=\"{$qr_code_data}\"/>";
            } catch (Exception $ex) {
                Civi::log()->warning("Couldn't render QR code: " . $ex->getMessage());
            }
        }

        // add some event data
        static $event_data = null;
        if ($event_data === null) {
            if (!empty($this->runnerData->eventId)) {
                try {
                    $event_data = civicrm_api3('Event', 'getsingle', ['id' => $this->runnerData->eventId]);
                } catch (CiviCRM_API3_Exception $ex) {
                    $event_data = []; // don't look up again
                    Civi::log()->error("Error loading event [{$this->runnerData->eventId}]: " . $ex->getMessage());
                }
            }
        }
        $templateTokens['event'] = $event_data;


        // that's it:
        return $templateTokens;
    }
}
