<?php

declare(strict_types = 1);

/**
 * -------------------------------------------------------+
 * | SYSTOPIA Event Invitation                              |
 * | Copyright (C) 2020 SYSTOPIA                            |
 * | Author: B. Zschiedrich (zschiedrich@systopia.de)       |
 * +--------------------------------------------------------+
 * | This program is released as free software under the    |
 * | Affero GPL license. You can redistribute it and/or     |
 * | modify it under the terms of this license which you    |
 * | can read by viewing the included agpl.txt or online    |
 * | at www.gnu.org/licenses/agpl.html. Removal of this     |
 * | copyright header is strictly prohibited without        |
 * | written permission from the original author(s).        |
 * +-------------------------------------------------------
 */
class CRM_Eventinvitation_EventInvitationCode {
  public const PARTICIPANT_CODE_USAGE = 'invite';

  /**
   * @throws Exception
   */
  public static function generate(string|int $participantId): string {
    $code = CRM_Remotetools_SecureToken::generateEntityToken(
        'Participant',
        intval($participantId),
        NULL,
        self::PARTICIPANT_CODE_USAGE
    );

    return $code;
  }

  /**
   * @return int|null The participant ID or, if invalid, null.
   */
  public static function validate(string $code): int|null {
    $participantId = CRM_Remotetools_SecureToken::decodeEntityToken(
        'Participant',
        $code,
        self::PARTICIPANT_CODE_USAGE
    );

    return $participantId;
  }

}
