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
+--------------------------------------------------------*/

use CRM_Eventinvitation_ExtensionUtil as E;

class CRM_Eventinvitation_Form_Register extends CRM_Core_Form {

  public ?string $code = NULL;

  /**
   * @throws CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    // TODO: Show something here after registering!

    parent::buildQuickForm();

    $participantId = NULL;
    /** @var string|null $code */
    $code = CRM_Utils_Request::retrieve('code', 'String', $this);
    $this->code = $code;

    if ($this->code !== NULL) {
      $participantId = CRM_Eventinvitation_EventInvitationCode::validate($this->code);
    }

    if ($participantId === NULL) {
      $headline = E::ts('Invalid or expired invite code');
    }
    else {
      /** @var int $registrationCount */
      $registrationCount = civicrm_api3(
        'Participant',
        'getcount',
        [
          'id' => $participantId,
          'status_id' => 'Registered',
        ]
        );

      $eventName = civicrm_api3(
            'Participant',
            'getvalue',
            [
              'return' => 'event_title',
              'id' => $participantId,
            ]
        );

      $this->setTitle(E::ts("Registration for '%1'", [1 => $eventName]));

      if ($registrationCount > 0) {
        $headline = E::ts('Congratulations! You have successfully registered for the event "%1"!', [1 => $eventName]);
      }
      else {
        $headline = E::ts('Do you want to register for the event "%1"?', [1 => $eventName]);

        $this->addButtons(
              [
        [
          'type' => 'submit',
          'name' => E::ts('Register'),
          'isDefault' => TRUE,
        ],
              ]
          );
      }
    }

    $this->assign('headline', $headline);
  }

  /**
   * @throws CRM_Core_Exception
   */
  public function postProcess(): void {
    parent::postProcess();
    if ($this->code === NULL) {
      return;
    }

    $participantId = CRM_Eventinvitation_EventInvitationCode::validate($this->code);

    if ($participantId !== NULL) {
      civicrm_api3(
        'Participant',
        'create',
        [
          'id' => $participantId,
          'status_id' => 'Registered',
        ]
      );
    }
  }

}
