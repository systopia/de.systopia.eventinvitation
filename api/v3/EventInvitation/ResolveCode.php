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

/**
 * Specs for the resolve of an event invitation code.
 *
 * @param array<string, array<string, mixed>> $specs API specs
 */
function _civicrm_api3_event_invitation_resolve_code_spec(array &$specs):void {
  $specs['code'] = [
    'name' => 'code',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
    'title' => 'Event invitation code',
    'description' => '',
  ];
}

/**
 * Resolve an event invitation code.
 *
 * @param array<string, mixed> $params API parameters
 * @return array<string, mixed>
 * @throws CRM_Core_Exception
 */
function civicrm_api3_event_invitation_resolve_code(array $params):array {

  /** @var string $code */
  $code = $params['code'];

  if (!isset($code)) {
    // TODO: Better message?
    return civicrm_api3_create_error('No code given');
  }
  else {
    $participantId = CRM_Eventinvitation_EventInvitationCode::validate($code);

    if ($participantId === NULL) {
      return civicrm_api3_create_error('The code is invalid or expired.');
    }
    else {
      return civicrm_api3_create_success($participantId);
    }
  }
}
