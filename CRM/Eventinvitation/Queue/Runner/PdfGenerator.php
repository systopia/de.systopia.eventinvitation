<?php

/*-------------------------------------------------------+
| SYSTOPIA Event Invitation                              |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
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

class CRM_Eventinvitation_Queue_Runner_PdfGenerator extends CRM_Eventinvitation_Queue_Runner_Job
{
    public function __construct(
        CRM_Eventinvitation_Object_RunnerData $runnerData,
        int $offset
    ) {
        parent::__construct($runnerData, $offset);
    }


    /**
     * Generate a PDF per contact
     *
     * @param integer $contactId
     *   contact ID
     * @param array $templateTokens
     *   tokens
     *
     * @throws \CiviCRM_API3_Exception
     */
    protected function processContact($contactId, $templateTokens)
    {
        // get the template
        static $template = null;
        if ($template === null) {
            // fetch template
            $template = civicrm_api3('MessageTemplate', 'getsingle', [
                'id'     => $this->runnerData->templateId,
                'return' => 'msg_html,pdf_format_id',
            ]);
        }

        // render: first generate HTML
        $smarty = CRM_Core_Smarty::singleton();
        $smarty->assignAll($templateTokens);
        $html = $smarty->fetch('string:' . $template['msg_html']);

        // then create pdf file
        $pdf_filename    = E::ts("Invitation-%1.pdf", [1 => $contactId]);
        $pf_invoice_pdf  = CRM_Utils_PDF_Utils::html2pdf($html, $pdf_filename, TRUE, $template['pdf_format_id']);
        file_put_contents($this->runnerData->temp_dir . DIRECTORY_SEPARATOR . $pdf_filename, $pf_invoice_pdf);
    }
}
