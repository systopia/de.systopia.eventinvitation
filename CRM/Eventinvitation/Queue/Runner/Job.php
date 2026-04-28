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

use Civi\Api4\Participant;
use CRM_Eventinvitation_ExtensionUtil as E;
use chillerlan\QRCode\QRCode;

// phpcs:ignore Generic.NamingConventions.AbstractClassNamePrefix.Missing
abstract class CRM_Eventinvitation_Queue_Runner_Job {

  public string $title;

  protected CRM_Eventinvitation_Object_RunnerData $runnerData;

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
   * @param int $contactId
   *   contact ID
   * @param array<string, mixed> $templateTokens
   *   tokens
   *
   * @throws CRM_Core_Exception
   */
  abstract protected function processContact(int $contactId, array $templateTokens): void;

  /**
   * Dispatch the contacts to the processContact function
   *
   * @return true
   */
  public function run(): bool {
    foreach ($this->runnerData->contactIds as $contactId) {
      $transaction = new CRM_Core_Transaction();

      try {
        $participantId = $this->setParticipantToInvited($contactId);
        $templateTokens = $this->getTemplateTokens($participantId);
        $this->processContact((int) $contactId, $templateTokens);
      }
      // @ignoreException
      // @phpstan-ignore-next-line
      catch (Exception $error) {
        $transaction->rollback();
        Civi::log()->warning("Generating email/pdf for contact {$contactId} failed: " . $error->getMessage());
      }

      $transaction->commit();
    }

    return TRUE;
  }

  /**
   * Mark the contact to be 'Invited'.
   * Note that;
   *  - they might already be invited - in which case we do nothing
   *  - they might have already rejected, accepted, e.g. - in which case we also do nothing
   *
   * TODO: do we want to upgrade an existing invitation, e.g. the date?
   *
   * As a result: if there is already an existing participant for this contact/event, we do nothing.
   * @param int $contactId
   *   the contact that should be invited
   *
   * @throws CRM_Core_Exception
   *
   */
  protected function setParticipantToInvited(int $contactId): int {
    // check if there is/are already existing participants
    $existing_participant = Participant::get(FALSE)
      ->addWhere('event_id', '=', $this->runnerData->eventId)
      ->addWhere('contact_id', '=', $contactId);
    // When inviting as resources (de.systopia.resourceevent), filter for role and resource demand.
    if (!empty($this->runnerData->resourceDemandId)) {
      $existing_participant
        ->addWhere(
            'role_id',
            'LIKE',
            '%' . implode(CRM_Core_DAO::VALUE_SEPARATOR, [$this->runnerData->participantRoleId]) . '%'
        )
        ->addWhere('resource_information.resource_demand', '=', $this->runnerData->resourceDemandId);
    }
    $existing_participant = $existing_participant->execute()->first();

    if (!empty($existing_participant['id'])) {
      // there is one, use that!
      return $existing_participant['id'];

    }
    else {
      // if there isn't one: create
      $new_participant = Participant::create(FALSE)
        ->addValue('event_id', $this->runnerData->eventId)
        ->addValue('contact_id', $contactId)
        ->addValue('status_id:name', CRM_Eventinvitation_Upgrader::PARTICIPANT_STATUS_INVITED_NAME)
        ->addValue('role_id', $this->runnerData->participantRoleId)
        ->addValue('register_date', date('Y-m-d H:i:s'));
      // When inviting as resource (de.systopia.resourceevent), add resource demand value.
      if (!empty($this->runnerData->resourceDemandId)) {
        $new_participant
          ->addValue('resource_information.resource_demand', $this->runnerData->resourceDemandId);
      }
      $new_participant = $new_participant->execute()->single();

      return $new_participant['id'];
    }
  }

  /**
   * Generates template tokens for the parser.
   * @param int $participantId
   *
   * @return array<string, mixed>
   *   key => value for Smarty parsing
   */
  protected function getTemplateTokens(int $participantId): array {
    // collect some tokens for the
    $templateTokens = [];

    // get the invitation link
    $invitationCode = CRM_Eventinvitation_EventInvitationCode::generate($participantId);

    $settings = Civi::settings()->get(CRM_Eventinvitation_Form_Settings::SETTINGS_KEY);

    if (
        is_array($settings)
        && isset($settings[CRM_Eventinvitation_Form_Settings::LINK_TARGET_IS_CUSTOM_FORM_NAME])
        && 1 === $settings[CRM_Eventinvitation_Form_Settings::LINK_TARGET_IS_CUSTOM_FORM_NAME]
        && isset($settings[CRM_Eventinvitation_Form_Settings::CUSTOM_LINK_TARGET_FORM_NAME])
        && '' !== $settings[CRM_Eventinvitation_Form_Settings::CUSTOM_LINK_TARGET_FORM_NAME]
    ) {

      /** @var string $linkString */
      $linkString = $settings[CRM_Eventinvitation_Form_Settings::CUSTOM_LINK_TARGET_FORM_NAME];
      $link = $linkString;

      // replace the code token
      $link = preg_replace('/\{token\}/', $invitationCode, $link);

    }
    else {
      // NOTE: This must be adjusted if the URL in the menu XML is ever changed.
      $path = 'civicrm/eventinvitation/register';

      $link = CRM_Utils_System::url($path, ['code' => $invitationCode], TRUE);
    }
    $templateTokens[CRM_Eventinvitation_Form_Task_ContactSearch::TEMPLATE_CODE_TOKEN] = $link;

    // add a QR code
    if ($link !== '') {
      try {
        $qr_code = new QRCode();

        /** @var string $qrRaw */
        $qrRaw = $qr_code->render($link);

        $qr_code_data = $qrRaw;
        $templateTokens[CRM_Eventinvitation_Form_Task_ContactSearch::TEMPLATE_CODE_TOKEN_QR_DATA] = $qr_code_data;
        $qr_code_alt_text = E::ts('Registration QR Code');
        $imgTag = "<img alt=\"{$qr_code_alt_text}\" src=\"{$qr_code_data}\"/>";
        $templateTokens[CRM_Eventinvitation_Form_Task_ContactSearch::TEMPLATE_CODE_TOKEN_QR_IMG] = $imgTag;
      }
      // @ignoreException
      // @phpstan-ignore-next-line
      catch (Exception $ex) {
        Civi::log()->warning("Couldn't render QR code: " . $ex->getMessage());
      }
    }

    // add some event data
    static $event_data = NULL;
    if ($event_data === NULL) {
      if (isset($this->runnerData->eventId)) {
        try {
          $event_data = civicrm_api3('Event', 'getsingle', ['id' => $this->runnerData->eventId]);
        }
        catch (CRM_Core_Exception $ex) {
          // don't look up again
          $event_data = [];
          Civi::log()->error("Error loading event [{$this->runnerData->eventId}]: " . $ex->getMessage());
        }
      }
    }
    $templateTokens['event'] = $event_data;

    // that's it:
    return $templateTokens;
  }

}
