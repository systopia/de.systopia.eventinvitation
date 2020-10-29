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
    const EMAIL_BATCH_SIZE = 40; // TODO: What is a good size?
    const PDF_BATCH_SIZE = 20; // TODO: What is a good size?

    /**
     * Launch the runner for the e-mail sender.
     * @param CRM_Eventinvitation_Object_RunnerData $runnerData
     * @param string $emailSender
     * @param string $targetUrl The URL we shall redirect after the runner has been finished.
     */
    public static function launchEmailSender(
        CRM_Eventinvitation_Object_RunnerData $runnerData,
        string $emailSender,
        string $targetUrl
    ): void {
        // TODO: Could the two launch methods share some code that is the same? Via a third method maybe?

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
     * Launch the runner for the PDF generation.
     * @param string[] $contactIds
     * @param string $template
     * @param string $targetUrl The URL we shall redirect after the runner has been finished.
     */
    public static function launchPdfGenerator(array $contactIds, string $template, string $targetUrl): void
    {
        // FIXME: This is not properly implemented!

        $queue = CRM_Queue_Service::singleton()->create(
            [
                'type' => 'Sql',
                'name' => 'eventinvitation_pdf_generator_' . CRM_Core_Session::singleton()->getLoggedInContactID(),
                'reset' => true,
            ]
        );

        $dataCount = count($contactIds);

        for ($offset = 0; $offset < $dataCount; $offset += self::PDF_BATCH_SIZE) {
            $batchedContactIds = array_slice($contactIds, $offset, self::PDF_BATCH_SIZE);

            $queue->createItem(
                new CRM_Eventinvitation_Queue_Runner_PdfGenerator(
                    $batchedContactIds,
                    $template,
                    $offset,
                    self::PDF_BATCH_SIZE
                )
            );
        }

        $runner = new CRM_Queue_Runner(
            [
                'title' => E::ts('Generating PDFs.'),
                'queue' => $queue,
                'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
                'onEndUrl' => $targetUrl,
            ]
        );

        $runner->runAllViaWeb();
    }
}
