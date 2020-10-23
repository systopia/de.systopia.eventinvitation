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

class CRM_Eventinvitation_Form_Task_ContactSearch extends CRM_Contact_Form_Task
{
    private const EVENT_ELEMENT_NAME = 'event';
    private const PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME = 'pdfs_instead_of_emails';
    private const TEMPLATE_ELEMENT_NAME = 'template';
    private const EMAIL_SENDER_ELEMENT_NAME = 'email_sender';
    private const PARTICIPANT_ROLES_ELEMENT_NAME = 'participant_roles';

    private const SETTINGS_KEY = 'eventinvitation_form_task_contactsearch_settings';
    private const TEMPLATE_SETTINGS_KEY = 'template_default';

    // TODO: Find a better (more central) place for this constant!
    public const TEMPLATE_CODE_TOKEN = 'qr_event_invite_code';

    public function buildQuickForm()
    {
        parent::buildQuickForm();

        $this->addEntityRef(
            self::EVENT_ELEMENT_NAME,
            E::ts('Event'),
            [
                'entity' => 'Event',
                'api' => [
                    'params' => [
                        'is_active' => 1,
                        'limit' => 0,
                    ]
                ]
            ],
            true
        );

        $generatePdfChechbox = $this->add(
            'checkbox',
            self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME,
            E::ts('Generate PDFs instead of sending e-mails.')
        );
        $generatePdfChechbox->freeze(); // TODO: Unfreeze as soon as the PDF launcher is implemented.

        $templates = $this->getMessageTemplates();
        $this->add(
            'select',
            self::TEMPLATE_ELEMENT_NAME,
            E::ts('Template'),
            $templates,
            true,
            [
                'class' => 'crm-select2 huge',
            ]
        );

        $senderOptions = $this->getSenderOptions();
        $this->add(
            'select',
            self::EMAIL_SENDER_ELEMENT_NAME,
            E::ts('E-mail sender address'),
            $senderOptions,
            true,
            [
                'class' => 'crm-select2 huge',
            ]
        );

        $participantRoles = $this->getParticipantRoles();
        $this->add(
            'select',
            self::PARTICIPANT_ROLES_ELEMENT_NAME,
            E::ts('Participant role'),
            $participantRoles,
            true,
            [
                'class' => 'crm-select2 huge',
            ]
        );

        $defaults = [];

        $settings = Civi::settings()->get(self::SETTINGS_KEY);

        // Prefill the selected template if there is one in the settings:
        if ($settings && is_array($settings) && array_key_exists(self::TEMPLATE_SETTINGS_KEY, $settings)) {
            $defaults[self::TEMPLATE_ELEMENT_NAME] = $settings[self::TEMPLATE_SETTINGS_KEY];
        }

        $this->setDefaults($defaults);
    }

    public function validate()
    {
        $values = $this->exportValues(null, true); // TODO: Should this be $this->_submitValues['x'] instead?

        $shallBePdfs = false;

        if (array_key_exists(self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME, $values)) {
            $shallBePdfs = $values[self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME];
        }

        $templateId = $values[self::TEMPLATE_ELEMENT_NAME];
        $eventId = $values[self::EVENT_ELEMENT_NAME];
        $participantRoleId = $values[self::PARTICIPANT_ROLES_ELEMENT_NAME];

        $contactIds = $this->_contactIds;

        if (!$shallBePdfs && !$this->contactsHaveEmails($contactIds)) {
            $this->_errors[self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME] = E::ts("There are contacts that have no e-mail address.");
        }

        if ($this->contactsHaveNotInvitedParticipants($contactIds, $eventId, $participantRoleId)) {
            $this->_errors[self::EVENT_ELEMENT_NAME] = E::ts('There are contacts that are participants with a status other than "Invited".');
        }

        if (!$this->templateHasCodeToken($templateId)) {
            $this->_errors[self::TEMPLATE_ELEMENT_NAME] = E::ts('The given template includes no token for the invitation code/URL.');
        }

        parent::validate();

        $result = count($this->_errors) == 0;

        return $result;
    }

    private function contactsHaveEmails(array $contactIds): bool
    {
        $contactIdsAsCommaSeparatedList = implode(',', $contactIds);

        $query =
        "SELECT
            COUNT(*)
        FROM
        (
            SELECT
                DISTINCT contact_id
            FROM
                civicrm_email AS email
            LEFT JOIN
                civicrm_contact AS contact
                    ON
                        contact.id = email.contact_id
            WHERE
                email.contact_id IN ($contactIdsAsCommaSeparatedList)
                AND contact.do_not_email = 0
                AND contact.is_deleted = 0
        ) AS distinct_contact
        ";

        $emailCount = CRM_Core_DAO::singleValueQuery($query);

        $result = $emailCount == count($contactIds);

        return $result;
    }

    private function contactsHaveNotInvitedParticipants(array $contactIds, string $eventId, string $participantRoleId): bool
    {
        $queryResult = civicrm_api3(
            'Participant',
            'getcount',
            [
                'contact_id' => ['IN' => $contactIds],
                'event_id' => $eventId,
                'role_id' => $participantRoleId,
                'status_id' => ['!=' => CRM_Eventinvitation_Upgrader::PARTICIPANT_STATUS_INVITED_NAME],
            ]
        );

        $result = $queryResult['result'] > 0;

        return $result;
    }

    private function templateHasCodeToken(string $templateId): bool
    {
        $templateText = civicrm_api3(
            'MessageTemplate',
            'getvalue',
            [
                'return' => 'msg_text',
                'id' => $templateId,
            ]
        );

        $token = '{' . self::TEMPLATE_CODE_TOKEN . '}';

        $result = strpos($templateText, $token) !== false;

        return $result;
    }

    public function postProcess()
    {
        parent::postProcess();

        $values = $this->exportValues(null, true);

        $shallBePdfs = $values[self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME];
        $emailSenderId = $values[self::EMAIL_SENDER_ELEMENT_NAME];

        $senderOptions = $this->getSenderOptions();
        $emailSender = $senderOptions[$emailSenderId];

        $runnerData = new CRM_Eventinvitation_Object_RunnerData();
        $runnerData->contactIds = $this->_contactIds;
        $runnerData->eventId = $values[self::EVENT_ELEMENT_NAME];
        $runnerData->participantRoleId = $values[self::PARTICIPANT_ROLES_ELEMENT_NAME];
        $runnerData->templateId = $values[self::TEMPLATE_ELEMENT_NAME];

        // Forward back to the search:
        $targetUrl = CRM_Core_Session::singleton()->readUserContext();

        if ($shallBePdfs) {
            //CRM_Eventinvitation_Queue_Runner_Launcher::launchPdfGenerator($runnerData, $targetUrl);
            // TODO: Uncomment as soon as the PDF launcher is implemented.
        } else {
            CRM_Eventinvitation_Queue_Runner_Launcher::launchEmailSender($runnerData, $emailSender, $targetUrl);
        }
    }

    /**
     * Get a list of the available/allowed sender email addresses
     */
    private function getSenderOptions(): array
    {
        $list = [];
        $query = civicrm_api3(
            'OptionValue',
            'get',
            [
                'option_group_id' => 'from_email_address',
                'option.limit' => 0,
                'return' => 'value,label',
            ]
        );

        foreach ($query['values'] as $sender) {
            $list[$sender['value']] = $sender['label'];
        }

        return $list;
    }

    private function getMessageTemplates(): array
    {
        $list = [];
        $query = civicrm_api3(
            'MessageTemplate',
            'get',
            [
                'is_active' => 1,
                'option.limit' => 0,
                'return' => 'id,msg_title',
            ]
        );

        foreach ($query['values'] as $status) {
            $list[$status['id']] = $status['msg_title'];
        }

        return $list;
    }

    private function getParticipantRoles(): array
    {
        $list = [];
        $query = civicrm_api3(
            'OptionValue',
            'get',
            [
                'option_group_id' => 'participant_role',
                'option.limit' => 0,
                'return' => 'value,label',
            ]
        );

        foreach ($query['values'] as $role) {
            $list[$role['value']] = $role['label'];
        }

        return $list;
    }
}
