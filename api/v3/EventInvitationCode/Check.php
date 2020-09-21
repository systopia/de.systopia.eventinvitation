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

/**
 * Specs for the check of an event invitation code.
 *
 * @param array $specs API specs
 */
function _civicrm_api3_eventinvitationcode_check_spec(array &$specs)
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
function civicrm_api3_eventinvitationcode_check(array $params)
{
    $code = $params['code'];

    if (empty($code)) {
        return civicrm_api3_create_error('No code given'); // TODO: Better message?
    } else {
        $isValid = CRM_Eventinvitation_EventinvitationCode::check($code);

        return civicrm_api3_create_success($isValid);
    }
}
