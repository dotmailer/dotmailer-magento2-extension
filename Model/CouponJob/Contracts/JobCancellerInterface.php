<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Contracts;

/**
 * Cancels a bulk coupon job.
 *
 * Allows integrators to swap implementations via a di.xml preference without
 * patching core code.
 */
interface JobCancellerInterface
{
    /**
     * Message stamped on every importer row belonging to a cancelled job.
     */
    public const CANCELLED_MESSAGE = 'Coupon job cancelled';

    /**
     * Cancel a coupon job.
     *
     * Moves the job to the cancelled status, fails every one of its importer
     * batches that has not already been imported, and performs a final report
     * reconciliation so the batch/record counts remain accurate.
     *
     * @param int $couponJobId
     * @return bool True when the job was cancelled.
     */
    public function cancel(int $couponJobId): bool;
}
