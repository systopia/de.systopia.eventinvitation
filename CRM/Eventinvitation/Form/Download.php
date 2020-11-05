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
+--------------------------------------------------------*/
use CRM_Eventinvitation_ExtensionUtil as E;

/**
 * Page to download PDFs and go back to the result
 */
class CRM_Eventinvitation_Form_Download extends CRM_Core_Form {

    /** @var string the tmp folder holding the PDFs */
    public $tmp_folder;

    /** @var string the URL to return to */
    public $return_url;

    public function buildQuickForm()
    {
        $this->tmp_folder = CRM_Utils_Request::retrieve('tmp_folder', 'String', $this);
        $this->return_url = CRM_Utils_Request::retrieve('return_url', 'String', $this);

        $this->setTitle(E::ts("Your PDFs are ready for download."));
        $this->addButtons(
            [
                [
                    'type' => 'submit',
                    'name' => E::ts('Download'),
                    'icon' => 'fa-download',
                    'isDefault' => true,
                ],
                [
                    'type' => 'done',
                    'name' => E::ts('Back to Search'),
                    'isDefault' => false,
                ],
            ]
        );

        parent::buildQuickForm();
    }

    public function postProcess()
    {
        // this means somebody clicked download
        $vars = $this->exportValues();
        if (isset($vars['_qf_Download_submit'])) {
            // verify folder
            if (!preg_match('#/eventinvitation_pdf_generator_\w+$#', $this->tmp_folder)) {
                throw new Exception("Illegal path!");
            }

            // download PDFs


            // todo: verify folder
            try {
                // create ZIP file
                $zip = new ZipArchive();
                $filename = $this->tmp_folder . DIRECTORY_SEPARATOR . 'all.zip';
                $zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

                // add all Invitation-X.pdf files
                foreach (scandir($this->tmp_folder) as $file) {
                    if (preg_match('/Invitation-[0-9]+.pdf/', $file)) {
                        $zip->addFile($this->tmp_folder . DIRECTORY_SEPARATOR . $file, $file);
                    }
                }
                $zip->close();

                // offer download
                $data = file_get_contents($filename);
                CRM_Utils_System::download("Invitations.zip", 'application/zip', $data);

            } catch (Exception $ex) {
                CRM_Core_Session::setStatus(
                    E::ts("Error downloading PDF files: %1", [1 => $ex->getMessage()]),
                    E::ts("Download Error"),
                    'error');
            }

        } else if (isset($vars['_qf_Download_done'])) {
            // delete tmp folder
            foreach (scandir($this->tmp_folder) as $file) {
                if ($file != '.' && $file != '..') {
                    unlink($this->tmp_folder . DIRECTORY_SEPARATOR . $file);
                }
            }
            rmdir($this->tmp_folder);

            // go back
            CRM_Utils_System::redirect(base64_decode($this->return_url));
        }

        parent::postProcess();
    }
}
