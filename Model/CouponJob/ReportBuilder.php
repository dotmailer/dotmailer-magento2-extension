<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob;

use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\FailureLogInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\ReportBuilderInterface;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory as ImporterCollectionFactory;
use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;

/**
 * Builds and persists an aggregated report for a CouponJob.
 *
 * Called once per batch completion by CouponJobInProgressImportResponseHandler.
 * Each call receives the importer row ID, the per-contact failures, and the
 * imported-contact count for that batch directly — no message-field encoding needed.
 *
 * Both failures and per-batch imported counts are stored keyed by importer ID so
 * that retries replace rather than accumulate stale values. total_records_imported
 * is always derived by summing batch_records_imported.
 *
 * total_records and total_batches are preserved from the values seeded by
 * CouponStartConsumer and are never recomputed here.
 *
 * The load-modify-save is delegated to JobUpdater so concurrent batch completions
 * are serialised behind a row lock and cannot overwrite one another.
 */
class ReportBuilder implements ReportBuilderInterface
{
    /**
     * @var JobUpdaterInterface
     */
    private $jobUpdater;

    /**
     * @var ImporterCollectionFactory
     */
    private $importerCollectionFactory;

    /**
     * @var ReportInterfaceFactory
     */
    private $reportFactory;

    /**
     * @var FailureLogInterface
     */
    private $failureLog;

    /**
     * @param JobUpdaterInterface $jobUpdater
     * @param ImporterCollectionFactory $importerCollectionFactory
     * @param ReportInterfaceFactory $reportFactory
     * @param FailureLogInterface $failureLog
     */
    public function __construct(
        JobUpdaterInterface $jobUpdater,
        ImporterCollectionFactory $importerCollectionFactory,
        ReportInterfaceFactory $reportFactory,
        FailureLogInterface $failureLog
    ) {
        $this->jobUpdater = $jobUpdater;
        $this->importerCollectionFactory = $importerCollectionFactory;
        $this->reportFactory = $reportFactory;
        $this->failureLog = $failureLog;
    }

    /**
     * Rebuild and persist job_details for the given coupon job.
     *
     * Per-contact failures and the imported-contact count for the batch that just
     * finished are passed directly from CouponJobInProgressImportResponseHandler.
     * Failures are stored keyed by the importer row ID so each batch's slice is
     * replaced atomically on every call. The whole read-modify-write runs inside
     * JobUpdater's row lock so concurrent completions cannot lose updates.
     *
     * @param int $couponJobId
     * @param int $importerId The email_importer.id of the batch that just finished
     * @param int $recordsImported Number of contacts successfully imported in this batch
     * @param array $currentBatchFailures Per-contact failures for the current batch
     * @return void
     */
    public function build(
        int $couponJobId,
        int $importerId,
        int $recordsImported = 0,
        array $currentBatchFailures = []
    ): void {
        $this->failureLog->replaceBatch(
            $couponJobId,
            FailureLogInterface::CATEGORY_IMPORTS,
            $importerId,
            $currentBatchFailures
        );

        $this->jobUpdater->update(
            $couponJobId,
            function (CouponJob $couponJob) use ($couponJobId, $importerId, $recordsImported, $currentBatchFailures) {
                /** @var \Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report $existingReport */
                $existingReport = $couponJob->getJobDetails()->getReport();
                $batchRecordsImported = (array) (
                    $existingReport->getData(ReportInterface::BATCH_RECORDS_IMPORTED) ?? []
                );
                $batchRecordsImported[$importerId] = $recordsImported;

                $totals = $this->aggregate($couponJob, $couponJobId, $batchRecordsImported);

                $jobDetails = $couponJob->getJobDetails();
                $jobDetails->setReport($totals['report']);
                $jobDetails->setFailedImportsCount($importerId, count($currentBatchFailures));
                $couponJob->setJobDetails($jobDetails);

                if (!$this->isTerminal($couponJob)
                    && $totals['effectiveTotalBatches'] > 0
                    && $totals['totalBatchesProcessed'] >= $totals['effectiveTotalBatches']
                ) {
                    $couponJob->setStatus(CouponJobInterface::STATUS_COMPLETE);
                }
            }
        );
    }

    /**
     * Recalculate and persist job_details for the given coupon job.
     *
     * @param int $couponJobId
     * @return void
     */
    public function refresh(int $couponJobId): void
    {
        $this->jobUpdater->update(
            $couponJobId,
            function (CouponJob $couponJob) use ($couponJobId) {
                /** @var \Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report $existingReport */
                $existingReport = $couponJob->getJobDetails()->getReport();
                $batchRecordsImported = (array) (
                    $existingReport->getData(ReportInterface::BATCH_RECORDS_IMPORTED) ?? []
                );

                $totals = $this->aggregate($couponJob, $couponJobId, $batchRecordsImported);

                $jobDetails = $couponJob->getJobDetails();
                $jobDetails->setReport($totals['report']);
                $couponJob->setJobDetails($jobDetails);
            }
        );
    }

    /**
     * Build a fresh report struct from the job's batches and per-batch imported counts.
     *
     * The seeded total_records and total_batches denominators are preserved; only the
     * processed and imported counters are re-derived.
     *
     * @param CouponJob $couponJob
     * @param int $couponJobId
     * @param array $batchRecordsImported Per-batch imported counts keyed by importer ID
     * @return array{
     *     report: ReportInterface,
     *     totalBatchesProcessed: int,
     *     effectiveTotalBatches: int
     * }
     */
    private function aggregate(CouponJob $couponJob, int $couponJobId, array $batchRecordsImported): array
    {
        $batches = $this->importerCollectionFactory->create()
            ->getBatchesByCouponJobId($couponJobId);

        $totalBatches = $batches->getSize();
        $totalBatchesProcessed = 0;

        foreach ($batches as $batch) {
            $status = (int) $batch->getData('import_status');
            if ($status === ImporterModel::IMPORTED || $status === ImporterModel::FAILED) {
                $totalBatchesProcessed++;
            }
        }

        /** @var \Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report $existingReport */
        $existingReport = $couponJob->getJobDetails()->getReport();
        $seededTotalRecords = (int) ($existingReport->getData(ReportInterface::TOTAL_RECORDS) ?? 0);
        $seededTotalBatches = (int) ($existingReport->getData(ReportInterface::TOTAL_BATCHES) ?? 0);

        $effectiveTotalBatches = max($totalBatches, $seededTotalBatches);

        /** @var \Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report $report */
        $report = $this->reportFactory->create();
        $report->setData(ReportInterface::TOTAL_RECORDS, $seededTotalRecords);
        $report->setData(ReportInterface::TOTAL_BATCHES, $effectiveTotalBatches);
        $report->setData(ReportInterface::TOTAL_RECORDS_IMPORTED, array_sum($batchRecordsImported));
        $report->setData(ReportInterface::TOTAL_BATCHES_PROCESSED, $totalBatchesProcessed);
        $report->setData(ReportInterface::BATCH_RECORDS_IMPORTED, $batchRecordsImported);

        return [
            'report' => $report,
            'totalBatchesProcessed' => $totalBatchesProcessed,
            'effectiveTotalBatches' => $effectiveTotalBatches,
        ];
    }

    /**
     * Determine whether the job has already reached a terminal state.
     *
     * @param CouponJob $couponJob
     * @return bool
     */
    private function isTerminal(CouponJob $couponJob): bool
    {
        return in_array(
            (string) $couponJob->getData('status'),
            CouponJobInterface::TERMINAL_STATUSES,
            true
        );
    }
}
