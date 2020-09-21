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

// TODO: Name of the page
class CRM_Eventinvitation_Page_Invited extends CRM_Core_Page
{
    public function run()
    {
        CRM_Utils_System::setTitle(E::ts('Successfully invited'));

        // TODO: Do we need more here?
        // TODO: Is the template enough?

        parent::run();
    }
}
