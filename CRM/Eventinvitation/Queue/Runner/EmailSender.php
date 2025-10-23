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

class CRM_Eventinvitation_Queue_Runner_EmailSender extends CRM_Eventinvitation_Queue_Runner_Job
{
    /** @var string $template */
    protected $emailSender;

    public function __construct(
        CRM_Eventinvitation_Object_RunnerData $runnerData,
        $emailSender,
        int $offset
    ) {
        parent::__construct($runnerData, $offset);
        $this->emailSender = $emailSender;
    }


    /**
     * Send an email to the given contact
     *
     * @param integer $contactId
     *   contact ID
     * @param array $templateTokens
     *   tokens
     *
     * @throws \CRM_Core_Exception
     */
    protected function processContact($contactId, $templateTokens)
    {
        $contactData = civicrm_api3(
            'Contact',
            'getsingle',
            [
                'id' => $contactId,
                'return' => 'display_name,email'
            ]
        );
        $email = \Civi\Api4\Email::get(FALSE)
            ->selectRowCount()
            ->addWhere('contact_id', '=', $contactId)
            ->addWhere('email', '=', $contactData['email'])
            ->addWhere('on_hold', '=', 0)
            ->execute()
            ->count();
        if ($email >= 1) {
            $emailData = [
                'id' => $this->runnerData->templateId,
                'toName' => $contactData['display_name'],
                'toEmail' => $contactData['email'],
                'from' => $this->emailSender,
                'contactId' => $contactId,
                'tplParams' => $templateTokens,
            ];

            civicrm_api3('MessageTemplate', 'send', $emailData);
        }
    }
}
