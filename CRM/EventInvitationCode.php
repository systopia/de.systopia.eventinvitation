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

class CRM_Eventinvitation_EventinvitationCode extends CRM_Contact_Form_Task
{
    public static function generate(int $contactId): string
    {
        // TODO: Generate code!
        // NOTE: Kodiert Participant-ID
        // NOTE: Fälschungssicher, nicht ratbar
        // NOTE: Gut lesbar als Base32 oder ähnliches ausgeben.
        // NOTE: Unter Nutzung des Kontakthash?

        // NOTE: Da der Kontakthash für andere Dinge benutzt werden kann (überall in CiviCRM und von jeder Erweiterung)
        //       kann er nicht als (kryptografisch) sicher betrachtet werden. Damit steht kein anderer Schlüsselkandidat
        //       für eine Verschlüsselung der Participant-ID zur Verfügung und es muss auf eine Hashfunktion mit offen
        //       liegender Participant-ID gewechselt werden.
        //       Einzige Ausnahme: Verschleierung

        return '';
    }

    // TODO: Rename to "validate" or something else?
    public static function check(string $code): bool
    {
        // TODO: Check/validate!
        // FIXME: Was genau?

        // TODO: Gegebenenfalls Dekodieren (Base32 etc.)

        return false;
    }
}
