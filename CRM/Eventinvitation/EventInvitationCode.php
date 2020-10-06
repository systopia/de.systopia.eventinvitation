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

class CRM_Eventinvitation_EventInvitationCode
{
    private const PARTICIPANT_CODE_USAGE = 'invite';

    public static function generate(string $participantId): string
    {
        $code = CRM_Remotetools_SecureToken::generateEntityToken(
            'Participant',
            $participantId,
            null,
            self::PARTICIPANT_CODE_USAGE
        );

        return $code;
    }

    /**
     * @return string|null The participant ID or, if invalid, null.
     */
    public static function validate(string $code)
    {
        $participantId = CRM_Remotetools_SecureToken::decodeEntityToken(
            'Participant',
            $code,
            self::PARTICIPANT_CODE_USAGE
        );

        return $participantId;
    }
}
