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

        $this->setTitle(E::ts("Your Invitation PDFs are ready for download."));
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
                $filename = $this->tmp_folder . DIRECTORY_SEPARATOR . 'all.zip';

                // first: try with command line tool to avoid memory issues
                $has_error = 0;
                try {
                    $output = null;
                    $pattern = E::ts("Invitation-%1.pdf", [1 => '*']);
                    $command = "cd {$this->tmp_folder} && zip all.zip {$pattern}";
                    Civi::log()->debug("EventInvitation executing '{$command}' to zip generated pdfs...");
                    $timestamp = microtime(true);
                    exec($command, $output, $has_error);
                    $runtime = microtime(true) - $timestamp;
                    Civi::log()->debug("EventInvitation zip command took {$runtime}s");
                } catch (Exception $ex) {
                    $has_error = 1;
                }

                // if this didn't work, use PHP (memory intensive)
                if ($has_error || !file_exists($filename)) {
                    // this didn't work, use the
                    $zip = new ZipArchive();
                    $zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

                    // add all Invitation-X.pdf files
                    foreach (scandir($this->tmp_folder) as $file) {
                        $pattern = E::ts("Invitation-%1.pdf", [1 => '[0-9]+']);
                        if (preg_match("/{$pattern}/", $file)) {
                            $zip->addFile($this->tmp_folder . DIRECTORY_SEPARATOR . $file, $file);
                        }
                    }
                    $zip->close();
                }

                // trigger download
                if (file_exists($filename)) {
                    // set file metadata
                    header('Content-Type: application/zip');
                    header("Content-Disposition: attachment; filename=" . E::ts("Invitations.zip"));
                    header('Content-Length: ' . filesize($filename));

                    // dump file contents in stream and exit
                    // caution: big files need to be treated carefully, to not cause out of memory errors
                    // based on: https://zinoui.com/blog/download-large-files-with-php

                    // first: disable output buffering
                    if (ob_get_level()) ob_end_clean();

                    // then read the file chunk by chunk and write to output (echo)
                    $chunkSize = 16 * 1024 * 1024; // 16 MB chunks
                    $handle = fopen($filename, 'rb');
                    while (!feof($handle)) {
                        $buffer = fread($handle, $chunkSize);
                        echo $buffer;
                        ob_flush();
                        flush();
                    }
                    fclose($handle);

                    // delete the zip file
                    unlink($filename);

                    // we're done
                    CRM_Utils_System::civiExit();

                } else {
                    // this file really should exist...
                    throw new Exception(E::ts("ZIP file couldn't be generated. Contact the author."));
                }

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
