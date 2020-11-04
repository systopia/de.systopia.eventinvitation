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
 * Contains the data stored and used by the runners.
 */
class CRM_Eventinvitation_Object_RunnerData extends CRM_Eventinvitation_Object_BaseClass
{
    /** @var string[] $contactIds */
    public $contactIds;

    /** @var string $eventId */
    public $eventId;

    /** @var string $participantRoleId */
    public $participantRoleId;

    /** @var string $templateId */
    public $templateId;

    /** @var string $temp_dir */
    public $temp_dir;

}
