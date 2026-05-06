<?php

declare(strict_types = 1);

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

use Civi\Api4\ResourceDemand;
use CRM_Eventinvitation_ExtensionUtil as E;

class CRM_Eventinvitation_Form_Task_ContactSearch extends CRM_Contact_Form_Task {
  public const EVENT_ELEMENT_NAME = 'event';
  public const PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME = 'pdfs_instead_of_emails';
  public const TEMPLATE_ELEMENT_NAME = 'template';
  public const EMAIL_SENDER_ELEMENT_NAME = 'email_sender';
  public const PARTICIPANT_ROLES_ELEMENT_NAME = 'participant_roles';

  public const SETTINGS_KEY = 'eventinvitation_form_task_contactsearch_settings';
  public const TEMPLATE_SETTINGS_KEY = 'template_default';

  // TODO: Find a better (more central) place for this constant!
  public const TEMPLATE_CODE_TOKEN = 'qr_event_invite_code';
  public const TEMPLATE_CODE_TOKEN_QR_DATA = 'qr_event_invite_code_data';
  public const TEMPLATE_CODE_TOKEN_QR_IMG = 'qr_event_invite_code_img';

  /**
   * @throws CRM_Core_Exception
   */
  public function buildQuickForm(): void {

    parent::buildQuickForm();

    $this->setTitle(E::ts('Inviting %1 Contacts', [1 => count($this->_contactIds)]));

    $this->addEntityRef(
        self::EVENT_ELEMENT_NAME,
        E::ts('Event'),
        [
          'entity' => 'Event',
          'api' => [
            'params' => [
              'is_active' => 1,
              'limit' => 0,
            ],
          ],
        ],
        TRUE
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
                'IN' => array_keys(CRM_Resource_Types::getForEntityTable('civicrm_contact')),
              ],
              'limit' => 0,
            ],
          ],
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

    $this->add(
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
        TRUE,
        [
          'class' => 'crm-select2 huge',
          'placeholder' => TRUE,
        ]
    );

    $senderOptions = $this->getSenderOptions();
    $this->add(
        'select',
        self::EMAIL_SENDER_ELEMENT_NAME,
        E::ts('E-mail sender address'),
        $senderOptions,
        TRUE,
        [
          'class' => 'crm-select2 huge',
          'placeholder' => TRUE,
        ]
    );

    $participantRoles = $this->getParticipantRoles();
    $this->add(
        'select',
        self::PARTICIPANT_ROLES_ELEMENT_NAME,
        E::ts('Participant role'),
        $participantRoles,
        TRUE,
        [
          'class' => 'crm-select2 huge',
          'placeholder' => TRUE,
        ]
    );

    // set default values
    $this->setDefaults([
      self::TEMPLATE_ELEMENT_NAME          => Civi::settings()->get('event_invitation_default_template'),
      self::EMAIL_SENDER_ELEMENT_NAME      => Civi::settings()->get('event_invitation_default_sender'),
      self::PARTICIPANT_ROLES_ELEMENT_NAME => Civi::settings()->get('event_invitation_default_role'),
    ]);
  }

  /**
   * @throws CRM_Core_Exception
   * @throws Civi\API\Exception\UnauthorizedException
   */
  public function validate():bool {
    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    // TODO: Should this be $this->_submitValues['x'] instead?
    $values = $this->exportValues(NULL, TRUE);

    $manager = CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('de.systopia.resourceevent') === CRM_Extension_Manager::STATUS_INSTALLED) {
      // Validate invitations as resource for correct combinations of field values.
      $resource_role = Civi\Resourceevent\Utils::getResourceRole(TRUE);
      if (
        $values[self::PARTICIPANT_ROLES_ELEMENT_NAME] === key($resource_role)
        && empty($values['resource_demand_id'])
      ) {
        $msg = E::ts('A resource demand is mandatory when inviting contacts as resources.');
        $this->_errors['resource_demand_id'] = $msg;
      }
      if (
        !empty($values['resource_demand_id'])
        && $values[self::PARTICIPANT_ROLES_ELEMENT_NAME] !== key($resource_role)
      ) {
        $this->_errors[self::PARTICIPANT_ROLES_ELEMENT_NAME] = E::ts(
        'Select the role %1 for inviting contacts as resources.',
        [1 => reset($resource_role)]
        );
      }
      if (
        !empty($values['resource_demand_id'])
        && $values['event'] !== ResourceDemand::get(FALSE)
          ->addSelect('entity_id')
          ->addWhere('entity_table', '=', 'civicrm_event')
          ->addWhere('id', '=', $values['resource_demand_id'])
          ->execute()
          ->single()['entity_id']
      ) {
        $this->_errors['resource_demand_id'] = E::ts('The resource demand does not belong to the selected event.');
      }
    }

    $shallBePdfs = FALSE;

    if (array_key_exists(self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME, $values)) {
      $shallBePdfs = $values[self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME];
    }

    $templateId = $values[self::TEMPLATE_ELEMENT_NAME];
    $eventId = $values[self::EVENT_ELEMENT_NAME];
    $participantRoleId = $values[self::PARTICIPANT_ROLES_ELEMENT_NAME];

    $contactIds = $this->_contactIds;

    // @phpstan-ignore argument.type
    if (!$shallBePdfs && !$this->contactsHaveEmails($contactIds)) {
      $msg = E::ts('There are contacts that have no usable e-mail address.');
      $this->_errors[self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME] = $msg;
    }

    // @phpstan-ignore argument.type
    if ($this->contactsHaveNotInvitedParticipants($contactIds, $eventId, $participantRoleId)) {
      $msg = E::ts('There are contacts that are participants with a status other than "Invited".');
      $this->_errors[self::EVENT_ELEMENT_NAME] = $msg;
    }

    if (!$this->templateHasCodeToken($templateId)) {
      $this->_errors[self::TEMPLATE_ELEMENT_NAME] =
                E::ts("The given template doesn't contain any of the code tokens for the invitation URL.");
    }

    parent::validate();

    return count($this->_errors) === 0;
  }

  /**
   * @phpstan-param list<int> $contactIds
   * @throws CRM_Core_Exception
   */
  private function contactsHaveEmails(array $contactIds): bool {
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
                AND email.on_hold = 0
                AND contact.do_not_email = 0
                AND contact.is_deleted = 0
        ) AS distinct_contact
        ";

    $emailCount = CRM_Core_DAO::singleValueQuery($query);

    return $emailCount === count($contactIds);
  }

  /**
   * Checks whether contacts have not invited participants.
   * @phpstan-param list<int> $contactIds
   * @throws CRM_Core_Exception
   */
  private function contactsHaveNotInvitedParticipants(
    array $contactIds,
    string $eventId,
    string $participantRoleId
  ): bool {
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
   * Check if the provided template ID contains at least one of
   *  the code tokens in either msg_text or msg_html
   *
   * @param int|string $templateId
   *   the template ID
   *
   * @return bool
   *   true if the template contains one of the tokens
   *
   * @throws CRM_Core_Exception
   */
  private function templateHasCodeToken(int|string $templateId): bool {
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
        if (isset($template[$type])) {
          if (str_contains($template[$type], $token_string)) {
            return TRUE;
          }
        }
      }
    }

    // none of the tokens could be found in the template
    return FALSE;
  }

  /**
   * @throws CRM_Core_Exception
   */
  public function postProcess(): void {
    parent::postProcess();

    $values = $this->exportValues(NULL, TRUE);

    // store defaults
    Civi::settings()->set('event_invitation_default_template', $values[self::TEMPLATE_ELEMENT_NAME] ?? '');
    Civi::settings()->set('event_invitation_default_sender', $values[self::EMAIL_SENDER_ELEMENT_NAME] ?? '');
    Civi::settings()->set('event_invitation_default_role', $values[self::PARTICIPANT_ROLES_ELEMENT_NAME] ?? '');

    // evaluate submission
    $shallBePdfs = isset($values[self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME]);
    $emailSenderId = $values[self::EMAIL_SENDER_ELEMENT_NAME];

    $senderOptions = $this->getSenderOptions();
    $emailSender = $senderOptions[$emailSenderId];

    $runnerData = new CRM_Eventinvitation_Object_RunnerData();
    // @phpstan-ignore assign.propertyType
    $runnerData->contactIds = $this->_contactIds;
    $runnerData->eventId = $values[self::EVENT_ELEMENT_NAME];
    $runnerData->participantRoleId = $values[self::PARTICIPANT_ROLES_ELEMENT_NAME];
    if (isset($values['resource_demand_id'])) {
      $runnerData->resourceDemandId = $values['resource_demand_id'];
    }
    $runnerData->templateId = $values[self::TEMPLATE_ELEMENT_NAME];

    // Forward back to the search:
    $targetUrl = html_entity_decode(CRM_Core_Session::singleton()->readUserContext());

    if ($shallBePdfs) {
      CRM_Eventinvitation_Queue_Runner_Launcher::launchPdfGenerator($runnerData, $targetUrl);
    }
    else {
      CRM_Eventinvitation_Queue_Runner_Launcher::launchEmailSender($runnerData, $emailSender, $targetUrl);
    }
  }

  /**
   * Get a list of the available/allowed sender email addresses.
   * @return array<string,string>
   *   'email@example.com' => 'Display Name',
   * @throws CRM_Core_Exception
   */
  private function getSenderOptions(): array {

    /** @var array<string,string> $list */
    $list = [];
    /** @phpstan-var array{values: array<int, array{value: string, label: string}>} $query */
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

  /**
   * Returns the message templates.
   * @return array<int,string>
   *   17 => 'Display Name'
   * @throws CRM_Core_Exception
   */
  private function getMessageTemplates(): array {
    $list = [];
    $query = (array) civicrm_api3(
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

  /**
   * Returns the roles of participants.
   * @return array<string, string>
   *   '1' => 'Attendee'
   * @throws CRM_Core_Exception
   */
  private function getParticipantRoles(): array {
    $list = [];
    $query = (array) civicrm_api3(
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
