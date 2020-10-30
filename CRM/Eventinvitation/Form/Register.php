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
+--------------------------------------------------------*/

use CRM_Eventinvitation_ExtensionUtil as E;

class CRM_Eventinvitation_Form_Register extends CRM_Core_Form
{
    /** @var string $code */
    public $code;

    public function buildQuickForm()
    {
        // TODO: Show something here after registering!

        parent::buildQuickForm();

        $participantId = null;
        $this->code = CRM_Utils_Request::retrieve('code', 'String', $this);

        if ($this->code) {
            $participantId = CRM_Eventinvitation_EventInvitationCode::validate($this->code);
        }

        if ($participantId === null) {
            $headline = E::ts('Invalid or expired invite code');
        } else {
            $isAlreadyRegistered = civicrm_api3(
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

            if ($isAlreadyRegistered) {
                $headline = E::ts('Congratulations! You have successfully registered for the event "%1"!', [1 => $eventName]);
            } else {
                $headline = E::ts('Do you want to register for the event "%1"?', [1 => $eventName]);

                $this->addButtons(
                    [
                        [
                            'type' => 'submit',
                            'name' => E::ts('Register'),
                            'isDefault' => true,
                        ],
                    ]
                );
            }
        }

        $this->assign('headline', $headline);
    }

    public function postProcess()
    {
        parent::postProcess();

        $participantId = CRM_Eventinvitation_EventInvitationCode::validate($this->code);

        if ($participantId === null) {
            // TODO: What should we do here?
        } else {
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
