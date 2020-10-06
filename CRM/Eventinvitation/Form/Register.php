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

class CRM_Eventinvitation_Page_Register extends CRM_Core_Form
{
    public function buildQuickForm()
    {
        parent::buildQuickForm();

        $code = $_REQUEST['cid'];

        $participantId = CRM_Eventinvitation_EventInvitationCode::validate($code);

        if ($participantId === null) {
            // TODO: What should we do here?
            $eventName = '';
        } else {
            $eventName = civicrm_api3(
                'Participant',
                'getvalue',
                [
                    'return' => 'event_title',
                    'id' => $participantId,
                ]
            );
        }

        $headline = E::ts('Do you want to register for the event "%1"?', [1 => $eventName]);

        $this->assign('headline', $headline);

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

    public function postProcess()
    {
        parent::postProcess();

        $code = $_REQUEST['cid'];

        $participantId = CRM_Eventinvitation_EventInvitationCode::validate($code);

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
