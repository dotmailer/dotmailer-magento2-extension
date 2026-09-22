<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Contracts;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\Validator\ValidatableStructInterface;

/**
 * Job Details Interface
 *
 * Represents the complete job details structure.
 *
 * Stores aggregate counts only: the report progress statistics (inside
 * {@see ReportInterface}), the per-batch failed-import counts and the total
 * failed-message count.
 *
 * @api
 */
interface JobDetailsInterface extends ValidatableStructInterface
{
    public const REPORT = 'report';
    public const FAILED_IMPORTS = 'failed_imports';
    public const FAILED_MESSAGES = 'failed_messages';
    public const VALIDATION_PATTERN = [
        self::REPORT => ReportInterface::VALIDATION_PATTERN,
        self::FAILED_IMPORTS => ['required|isInt'],
        self::FAILED_MESSAGES => [ "*" => ['required|isInt']],
    ];

    /**
     * Set the report details for the job.
     *
     * @param ReportInterface $report
     * @return $this
     */
    public function setReport(ReportInterface $report);

    /**
     * Get the report details for the job.
     *
     * @return ReportInterface
     */
    public function getReport(): ReportInterface;

    /**
     * Set the failed-import count for a single batch (override-on-retry).
     *
     * @param int $importerId The email_importer.id of the batch
     * @param int $count Number of failed per-contact imports for the batch
     * @return $this
     */
    public function setFailedImportsCount(int $importerId, int $count);

    /**
     * Get the total failed-import count across all batches.
     *
     * @return int
     */
    public function getFailedImportsCount(): int;

    /**
     * Get the per-batch failed-import counts, keyed by importer ID.
     *
     * @return array<int|string, int>
     */
    public function getFailedImportsByBatch(): array;

    /**
     * Set the total failed-message count for the job.
     *
     * @param int $count
     * @return $this
     */
    public function setFailedMessagesCount(int $count);

    /**
     * Get the total failed-message count for the job.
     *
     * @return int
     */
    public function getFailedMessagesCount(): int;
}
