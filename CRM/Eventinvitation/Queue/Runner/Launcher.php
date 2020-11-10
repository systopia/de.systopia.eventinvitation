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

/**
 * The launcher for a queues/runners.
 */
abstract class CRM_Eventinvitation_Queue_Runner_Launcher
{
    const EMAIL_BATCH_SIZE = 20;
    const PDF_BATCH_SIZE = 20;

    /**
     * Launch the runner for the e-mail sender.
     *
     * @param CRM_Eventinvitation_Object_RunnerData $runnerData
     * @param string $emailSender
     * @param string $targetUrl The URL we shall redirect after the runner has been finished.
     */
    public static function launchEmailSender(
        CRM_Eventinvitation_Object_RunnerData $runnerData,
        string $emailSender,
        string $targetUrl
    ): void {

        $queue = CRM_Queue_Service::singleton()->create(
            [
                'type' => 'Sql',
                'name' => 'eventinvitation_email_sender_' . CRM_Core_Session::singleton()->getLoggedInContactID(),
                'reset' => true,
            ]
        );

        $dataCount = count($runnerData->contactIds);

        for ($offset = 0; $offset < $dataCount; $offset += self::EMAIL_BATCH_SIZE) {
            $batchedContactIds = array_slice($runnerData->contactIds, $offset, self::EMAIL_BATCH_SIZE);

            $batchedRunnerData = new CRM_Eventinvitation_Object_RunnerData($runnerData->toArray());
            $batchedRunnerData->contactIds = $batchedContactIds;

            $queue->createItem(
                new CRM_Eventinvitation_Queue_Runner_EmailSender(
                    $batchedRunnerData,
                    $emailSender,
                    $offset
                )
            );
        }

        $runner = new CRM_Queue_Runner(
            [
                'title' => E::ts('Sending e-mails.'),
                'queue' => $queue,
                'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
                'onEndUrl' => $targetUrl,
            ]
        );

        $runner->runAllViaWeb();
    }

    /**
     * Launch the runner for the PDF Generator
     *
     * @param CRM_Eventinvitation_Object_RunnerData $runnerData
     * @param string $targetUrl The URL we shall redirect after the runner has been finished.
     */
    public static function launchPdfGenerator(
        CRM_Eventinvitation_Object_RunnerData $runnerData,
        string $targetUrl
    ): void {

        // create a tmp folder to generate the PDFs in
        $temp_folder = tempnam(sys_get_temp_dir(),'eventinvitation_pdf_generator_');
        if (file_exists($temp_folder)) { unlink($temp_folder); }
        mkdir($temp_folder);
        $runnerData->temp_dir = $temp_folder;

        // create a runner queue
        $queue = CRM_Queue_Service::singleton()->create(
            [
                'type' => 'Sql',
                'name' => 'eventinvitation_pdf_generator_' . CRM_Core_Session::singleton()->getLoggedInContactID(),
                'reset' => true,
            ]
        );

        // fill the runner queue
        $dataCount = count($runnerData->contactIds);

        for ($offset = 0; $offset < $dataCount; $offset += self::EMAIL_BATCH_SIZE) {
            $batchedContactIds = array_slice($runnerData->contactIds, $offset, self::EMAIL_BATCH_SIZE);

            $batchedRunnerData = new CRM_Eventinvitation_Object_RunnerData($runnerData->toArray());
            $batchedRunnerData->contactIds = $batchedContactIds;

            $queue->createItem(
                new CRM_Eventinvitation_Queue_Runner_PdfGenerator(
                    $batchedRunnerData,
                    $offset
                )
            );
        }

        // create the link to the download screen
        $return_link = base64_encode(CRM_Core_Session::singleton()->readUserContext());
        $download_link = CRM_Utils_System::url('civicrm/eventinvitation/download', "tmp_folder={$temp_folder}&return_url={$return_link}");
        $runner = new CRM_Queue_Runner(
            [
                'title' => E::ts('Generating PDFs.'),
                'queue' => $queue,
                'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
                'onEndUrl' => $download_link,
            ]
        );

        $runner->runAllViaWeb();
    }
}
