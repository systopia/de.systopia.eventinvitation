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
    const EVENT_ELEMENT_NAME = 'event';
    const PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME = 'pdfs_instead_of_emails';
    const TEMPLATE_ELEMENT_NAME = 'template';
    const EMAIL_SENDER_ELEMENT_NAME = 'email_sender';
    const PARTICIPANT_ROLES_ELEMENT_NAME = 'participant_roles';

    const SETTINGS_KEY = 'eventinvitation_form_task_contactsearch_settings';
    const TEMPLATE_SETTINGS_KEY = 'template_default';

    // TODO: Find a better (more central) place for this constant!
    const TEMPLATE_CODE_TOKEN = 'qr_event_invite_code';
    const TEMPLATE_CODE_TOKEN_QR_DATA = 'qr_event_invite_code_data';
    const TEMPLATE_CODE_TOKEN_QR_IMG = 'qr_event_invite_code_img';

    public function buildQuickForm()
    {

        parent::buildQuickForm();

        $this->setTitle(E::ts("Inviting %1 Contacts", [1 => count($this->_contactIds)]));

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

        // If de.systopia.resourceevent is installed, allow inviting as resource by selecting a resource demand.
        $manager = CRM_Extension_System::singleton()->getManager();
        if ($manager->getStatus('de.systopia.resourceevent') === CRM_Extension_Manager::STATUS_INSTALLED) {
            // Add field for selecting resource demand.
            $this->addEntityRef(
                'resource_demand_id',
                E::ts('Resource Demand'),
                [
                    'entity' => 'ResourceDemand',
                    'api' => [
                        'params' => [
                            'entity_table' => 'civicrm_event',
                            // Filter for contact resources only.
                            'resource_type_id' => [
                                'IN' => array_keys(CRM_Resource_Types::getForEntityTable('civicrm_contact'))
                            ],
                            'limit' => 0,
                        ]
                    ]
                ]
            );
            Civi::resources()->addScriptFile(
                E::LONG_NAME,
                'js/invite-resource.js'
            );
            $resource_role = Civi\Resourceevent\Utils::getResourceRole(TRUE);
            $this->assign('resource_role_label', reset($resource_role));
            Civi::resources()->addVars(E::SHORT_NAME, ['resource_role_id' => key($resource_role)]);
        }

        $generatePdfChechbox = $this->add(
            'checkbox',
            self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME,
            E::ts('Generate PDFs instead of sending e-mails.')
        );

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

        // set default values
        $this->setDefaults([
           self::TEMPLATE_ELEMENT_NAME          => Civi::settings()->get('event_invitation_default_template'),
           self::EMAIL_SENDER_ELEMENT_NAME      => Civi::settings()->get('event_invitation_default_sender'),
           self::PARTICIPANT_ROLES_ELEMENT_NAME => Civi::settings()->get('event_invitation_default_role'),
        ]);
    }

    public function validate()
    {
        $values = $this->exportValues(null, true); // TODO: Should this be $this->_submitValues['x'] instead?

        $manager = CRM_Extension_System::singleton()->getManager();
        if ($manager->getStatus('de.systopia.resourceevent') === CRM_Extension_Manager::STATUS_INSTALLED) {
            // Validate invitations as resource for correct combinations of field values.
            $resource_role = Civi\Resourceevent\Utils::getResourceRole(TRUE);
            if (
                $values[self::PARTICIPANT_ROLES_ELEMENT_NAME] == key($resource_role)
                && empty($values['resource_demand_id'])
            ) {
                $this->_errors['resource_demand_id'] = E::ts('A resource demand is mandatory when inviting contacts as resources.');
            }
            if (
                !empty($values['resource_demand_id'])
                && $values[self::PARTICIPANT_ROLES_ELEMENT_NAME] != key($resource_role)
            ) {
                $this->_errors[self::PARTICIPANT_ROLES_ELEMENT_NAME] = E::ts(
                    'Select the role %1 for inviting contacts as resources.',
                    [1 => reset($resource_role)]
                );
            }
            if (
                !empty($values['resource_demand_id'])
                && $values['event'] != \Civi\Api4\ResourceDemand::get(FALSE)
                ->addSelect('entity_id')
                ->addWhere('entity_table', '=', 'civicrm_event')
                ->addWhere('id', '=', $values['resource_demand_id'])
                ->execute()
                ->single()['entity_id']
            ) {
                $this->_errors['resource_demand_id'] = E::ts('The resource demand does not belong to the selected event.');
            }
        }

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
            $this->_errors[self::TEMPLATE_ELEMENT_NAME] =
                E::ts("The given template doesn't contain any of the code tokens for the invitation URL.");
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
        $non_invite_participant_count = civicrm_api3(
            'Participant',
            'getcount',
            [
                'contact_id' => ['IN' => $contactIds],
                'event_id' => $eventId,
                'role_id' => $participantRoleId,
                'status_id' => ['!=' => CRM_Eventinvitation_Upgrader::PARTICIPANT_STATUS_INVITED_NAME],
            ]
        );

        return $non_invite_participant_count > 0;
    }

    /**
     * Check if the provided template ID contains at least on of
     *  the code tokens in either msg_text or msg_html
     *
     * @param integer $templateId
     *   the template ID
     *
     * @return bool
     *   true if the template contains one of the tokens
     *
     * @throws \CiviCRM_API3_Exception
     */
    private function templateHasCodeToken(string $templateId): bool
    {
        $template = civicrm_api3(
            'MessageTemplate',
            'getsingle',
            [
                'return' => 'msg_text,msg_html',
                'id' => $templateId,
            ]
        );

        // check if any of the tokens are present
        $tokens = [self::TEMPLATE_CODE_TOKEN, self::TEMPLATE_CODE_TOKEN_QR_DATA, self::TEMPLATE_CODE_TOKEN_QR_IMG];
        foreach ($tokens as $token) {
            $token_string = '{$' . $token . '}';
            foreach (['msg_text', 'msg_html'] as $type) {
                if (!empty($template[$type])) {
                    if (strpos($template[$type], $token_string) !== false) {
                        return true;
                    }
                }
            }
        }

        // none of the tokens could be found in the template
        return false;
    }

    public function postProcess()
    {
        parent::postProcess();

        $values = $this->exportValues(null, true);

        // store defaults
        Civi::settings()->set('event_invitation_default_template',
                              CRM_Utils_Array::value(self::TEMPLATE_ELEMENT_NAME, $values, ''));
        Civi::settings()->set('event_invitation_default_sender',
                              CRM_Utils_Array::value(self::EMAIL_SENDER_ELEMENT_NAME, $values, ''));
        Civi::settings()->set('event_invitation_default_role',
                              CRM_Utils_Array::value(self::PARTICIPANT_ROLES_ELEMENT_NAME, $values, ''));

        // evaluate submission
        $shallBePdfs = !empty($values[self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME]);
        $emailSenderId = $values[self::EMAIL_SENDER_ELEMENT_NAME];

        $senderOptions = $this->getSenderOptions();
        $emailSender = $senderOptions[$emailSenderId];

        $runnerData = new CRM_Eventinvitation_Object_RunnerData();
        $runnerData->contactIds = $this->_contactIds;
        $runnerData->eventId = $values[self::EVENT_ELEMENT_NAME];
        $runnerData->participantRoleId = $values[self::PARTICIPANT_ROLES_ELEMENT_NAME];
        if (!empty($values['resource_demand_id'])) {
            $runnerData->resourceDemandId = $values['resource_demand_id'];
        }
        $runnerData->templateId = $values[self::TEMPLATE_ELEMENT_NAME];

        // Forward back to the search:
        $targetUrl = html_entity_decode(CRM_Core_Session::singleton()->readUserContext());

        if ($shallBePdfs) {
            CRM_Eventinvitation_Queue_Runner_Launcher::launchPdfGenerator($runnerData, $targetUrl);
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
                'workflow_id' => ['IS NULL' => 1],
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
                'is_active' => TRUE,
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
