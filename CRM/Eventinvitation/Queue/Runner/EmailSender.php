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

class CRM_Eventinvitation_Queue_Runner_EmailSender
{
    /** @var string $title Will be set as title by the runner. */
    public $title;

    /** @var string[] $contactIds The contacts that shall be added to the group. */
    protected $contactIds;

    /** @var string $template */
    protected $template;

    /**
     * @param int $offset The contacts offset for this runner instance.
     * @param int $count The number of contacts this runner instance shall work on.
     */
    public function __construct(array $contactIds, string $template, int $offset, int $count)
    {
        $this->contactIds = $contactIds;
        $this->template = $template;

        $this->title = E::ts('Sending e-mails %1 to %2.', [1 => $offset + 1, 2 => $offset + $count]);
    }

    public function run(): bool
    {
        // TODO: Send e-mails!
        // https://github.com/systopia/de.systopia.eventmessages/blob/master/CRM/Eventmessages/SendMail.php

        // TODO: Empfänger E-Mail (+Name?)
        // TODO: Absender E-Mail (+Name?) -> Fabian abwarten.
        // TODO: Was noch genau?
        // TODO: Der Launcher muss diese Werte übergeben!

        return true;
    }
}
