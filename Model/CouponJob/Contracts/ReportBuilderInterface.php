<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Contracts;

/**
 * Interface for building and persisting aggregated reports for a CouponJob.
 *
 * Allows integrators to swap implementations via di.xml preference
 * without patching core code.
 */
interface ReportBuilderInterface
{
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
    ): void;

    /**
     * Recalculate and persist job_details for the given coupon job.
     *
     * Unlike build(), this is not tied to a single batch: it re-derives the
     * aggregate counters from the batches that already exist and from the
     * per-batch imported counts already recorded. Nothing is added, replaced or
     * removed — no failure logs are rewritten and no batch slice is reset.
     *
     * Used when a job reaches a terminal state outside the normal batch flow
     * (for example an admin cancellation) and the report needs one final,
     * accurate reconciliation.
     *
     * @param int $couponJobId
     * @return void
     */
    public function refresh(int $couponJobId): void;
}
