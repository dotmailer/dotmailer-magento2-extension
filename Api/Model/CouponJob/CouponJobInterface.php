<?php

namespace Dotdigitalgroup\Email\Api\Model\CouponJob;

interface CouponJobInterface
{
    /**
     * Job is pending (queued but not yet started)
     */
    public const STATUS_PENDING = 'pending';

    /**
     * Job is currently processing
     */
    public const STATUS_PROCESSING = 'processing';

    /**
     * Job completed successfully
     */
    public const STATUS_COMPLETE = 'complete';

    /**
     * Job failed
     */
    public const STATUS_FAILED = 'failed';

    /**
     * Job was cancelled by an admin user
     */
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses from which no further work will ever be started.
     *
     * A job in one of these states must never be resurrected, and its queued
     * messages and importer batches must be skipped.
     */
    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETE,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Statuses from which a job may still be cancelled.
     */
    public const CANCELLABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
    ];

    /**
     * Job Allowed status values
     * - pending
     * - processing
     * - complete
     * - failed
     * - cancelled
     *
     * @return array
     */
    public function getValidStatuses(): array;
}
