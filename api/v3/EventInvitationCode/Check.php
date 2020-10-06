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

// TODO: API wie in https://github.com/systopia/de.systopia.remotetools/blob/master/api/v3/RemoteContact/GetRoles.php
//       -> EventInvitation.ResolveCode Code zu ParticipiantId/Fehler

/**
 * Specs for the check of an event invitation code.
 *
 * @param array $specs API specs
 */
function _civicrm_api3_event_invitation_code_check_spec(array &$specs)
{
    $specs['code'] = [
        'name' => 'code',
        'api.required' => 1,
        'type' => CRM_Utils_Type::T_STRING,
        'title' => 'Event invitation code',
        'description' => '',
    ];
}

/**
 * Check an event invitation code.
 *
 * @param array $params API parameters
 * @return array result
 */
function civicrm_api3_event_invitation_code_check(array $params)
{
    $code = $params['code'];

    if (empty($code)) {
        return civicrm_api3_create_error('No code given'); // TODO: Better message?
    } else {
        $participantId = CRM_Eventinvitation_EventInvitationCode::validate($code);

        if ($participantId === null) {
            return civicrm_api3_create_error('The code is invalid or expired.');
        } else {
            return civicrm_api3_create_success($participantId);
        }
    }
}
