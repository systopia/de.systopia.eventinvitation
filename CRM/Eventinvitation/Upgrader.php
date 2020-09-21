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

class CRM_Eventinvitation_Upgrader_Base
{
    public function onInstall(): void
    {
        $apiResult = civicrm_api3(
            'ParticipantStatusType',
            'get',
            [
                'name' => 'Invited'
            ]
        );

        if ($apiResult['count'] === 0) {
            civicrm_api3(
                'ParticipantStatusType',
                'create',
                [
                    'name' => 'Invited'
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
                'name' => 'Invited'
            ]
        );

        civicrm_api3(
            'ParticipantStatusType',
            'delete',
            [
                'id' => $apiResult['id']
            ]
        );
    }
}
