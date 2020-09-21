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

/**
 * Specs for the creation of an event invitation code.
 *
 * @param array $specs API specs
 */
function _civicrm_api3_eventinvitationcode_create_spec(array &$specs)
{
    $specs['contact_id'] = [
        'name' => 'contact_id',
        'api.required' => 1,
        'type' => CRM_Utils_Type::T_STRING,
        'title' => 'Contact ID',
        'description' => 'Single contact ID or comma-separated list',
    ];
}

/**
 * Create an event invitation code.
 *
 * @param array $params API parameters
 * @return array result
 */
function civicrm_api3_eventinvitationcode_create(array $params)
{
    $contact_ids = $params['contact_id'];

    if (is_array($params['contact_id'])) {
        $contact_ids = $params['contact_id'];
    } elseif (is_numeric($params['contact_id'])) {
        $contact_ids = [(int) $params['contact_id']];
    } else {
        $contact_ids = explode(',', $params['contact_id']);
    }

    if (empty($contact_ids)) {
        return civicrm_api3_create_error('No code given'); // TODO: Better message?
    } else {
        $codes = [];
        foreach ($contact_ids as $contact_id) {
            $code = CRM_Eventinvitation_EventinvitationCode::generate($contact_id);
            $codes[] = $code;
        }

        return civicrm_api3_create_success($codes);
    }
}
