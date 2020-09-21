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

class CRM_Eventinvitation_Form_Task_ContactSearch extends CRM_Contact_Form_Task
{
    private const EVENT_ELEMENT_NAME = 'event';
    private const PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME = 'pdfs_instead_of_emails';
    private const TEMPLATE_ELEMENT_NAME = 'template';

    public function buildQuickForm()
    {
        parent::buildQuickForm();

        $this->addEntityRef(
            self::EVENT_ELEMENT_NAME,
            E::ts('Event'),
            [
                'entity' => 'Event',
                'api' => [
                    'params' => [
                        'is_active' => 1,
                        'limit' => 0,
                    ]
                ]
            ],
            true
        );

        $this->add(
            'checkbox',
            self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME,
            E::ts('Generate PDFs instead of sending e-mails.')
        );
        // TODO: Should this be replaced with a list to select from?

        $this->addEntityRef(
            self::TEMPLATE_ELEMENT_NAME,
            E::ts('Template'),
            [
                'entity' => 'MessageTemplate',
                'api' => [
                    'params' => [
                        'is_active' => 1,
                        'limit' => 0,
                    ] // TODO: sonstige Parameter
                ]
            ],
            true
        ); // TODO: Liste?

        // TODO: Vorauswahl für die letztgenutzte Vorlage.

        // TODO: Forms für E-Mailoptionen (Abesender etc.)

        // TODO: Rolle für Participant? -> Liste aller Rollen
    }

    public function validate()
    {
        // TODO: Haben die Kontakte E-Mails(vorhanden, aktiv_ do_not_email)/Adressen(vorhanden, do_not_mail)? -> Per SQL!
        // TODO: Haben die Kontakte bereits Participants (mit Status nicht Invited)? -> Woher die Information nehmen? Wie prüfen?
        //       -> Tabelle/Objekt Participant -> Event zu Kontakt -> Rolle beachten!
        // TODO: Hat die gewählte Vorlage das Token für QR-Code oder Link? -> Wie kommt man an den Vorlageninhalt?
        //       -> Text aus API, Token selbst ausdenken -> {$qr_event_invite_code} + url.

        // $this->_submitValues['x']
        // $this->_errors['y'] = E::ts("Text zu y");

        parent::validate();

        return (count($this->_errors) == 0);
    }

    public function postProcess()
    {
        parent::postProcess();

        $contactIds = $this->_contactIds;

        $values = $this->exportValues(null, true);
        $eventId = $values[self::EVENT_ELEMENT_NAME]; // TODO: Für Participants
        $shallBePdfs = $values[self::PDFS_INSTEAD_OF_EMAILS_ELEMENT_NAME];
        $template = $values[self::TEMPLATE_ELEMENT_NAME];

        // Forward back to the search:
        $targetUrl = CRM_Core_Session::singleton()->readUserContext();

        if ($shallBePdfs) {
            CRM_Eventinvitation_Queue_Runner_Launcher::launchPdfGenerator($contactIds, $template, $targetUrl);
        } else {
            CRM_Eventinvitation_Queue_Runner_Launcher::launchEmailSender($contactIds, $template, $targetUrl);
        }
    }

    // DONE: targetUrl soll Suchegergebnis sein, nicht Gruppe.
    // DONE: Parameter für "aktiv" und "limit"
}
