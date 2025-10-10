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

class CRM_Eventinvitation_Upgrader extends CRM_Extension_Upgrader_Base
{
    const PARTICIPANT_STATUS_INVITED_NAME = 'Invited';

    public function onInstall(): void
    {
        $apiResult = civicrm_api3(
            'ParticipantStatusType',
            'get',
            [
                'name' => self::PARTICIPANT_STATUS_INVITED_NAME
            ]
        );

        if ($apiResult['count'] === 0) {
            $max_weight = (int) CRM_Core_DAO::singleValueQuery("SELECT MAX(weight) FROM civicrm_participant_status_type");
            civicrm_api3(
                'ParticipantStatusType',
                'create',
                [
                    'name' => self::PARTICIPANT_STATUS_INVITED_NAME,
                    'label' => E::ts('Invited'),
                    'visibility_id' => 'public',
                    'class' => 'Waiting', // TODO: Should this be "Pending" instead?
                    'is_active' => 1,
                    'weight' => $max_weight + 1,
                    'is_reserved' => 1,
                    'is_counted' => 0,
                ]
            );
        }
    }

    public function onUninstall(): void
    {
        $apiResult = civicrm_api3(
            'ParticipantStatusType',
            'get',
            [
                'sequential' => 1,
                'name' => self::PARTICIPANT_STATUS_INVITED_NAME
            ]
        );

        if ($apiResult['count'] != 0) {
            civicrm_api3(
                'ParticipantStatusType',
                'delete',
                [
                    'id' => $apiResult['values'][0]['id']
                ]
            );
        }
    }
}
