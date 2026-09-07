<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob\Struct;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report;
use Magento\Framework\DataObject;

/**
 * Job Details Data Model
 *
 * Holds aggregate counts only: the report progress statistics (in {@see Report}),
 * the per-batch failed-import counts and the total failed-message count. The full
 * per-failure detail is written to dedicated per-job NDJSON log files by
 * {@see \Dotdigitalgroup\Email\Model\CouponJob\FailureLog}. Keeping the counts here
 * means the grid shows correct totals even if the log files are deleted.
 */
class JobDetails extends DataObject implements JobDetailsInterface
{
    /**
     * @var ReportInterfaceFactory
     */
    private $reportInterfaceFactory;

    /**
     * @param ReportInterfaceFactory $reportInterfaceFactory
     * @param array $data
     */
    public function __construct(
        ReportInterfaceFactory $reportInterfaceFactory,
        array $data = []
    ) {
        $this->reportInterfaceFactory = $reportInterfaceFactory;
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getValidationPattern(): array
    {
        return static::VALIDATION_PATTERN;
    }

    /**
     * Set the report details for the job.
     *
     * @param ReportInterface $report The report details to set.
     * @return $this
     */
    public function setReport(ReportInterface $report): JobDetails
    {
        /** @var Report $report */
        return $this->setData(self::REPORT, $report->getData());
    }

    /**
     * Get the report details for the job.
     *
     * @return ReportInterface The report details for the job.
     */
    public function getReport(): ReportInterface
    {
        return $this->reportInterfaceFactory->create(['data' => $this->getData(self::REPORT)]);
    }

    /**
     * Set the failed-import count for a single batch (override-on-retry).
     *
     * A count of zero clears any previously recorded value for that batch.
     *
     * @param int $importerId The email_importer.id of the batch
     * @param int $count Number of failed per-contact imports for the batch
     * @return $this
     */
    public function setFailedImportsCount(int $importerId, int $count): JobDetails
    {
        $all = $this->getData(self::FAILED_IMPORTS) ?? [];
        if ($count > 0) {
            $all[$importerId] = $count;
        } else {
            unset($all[$importerId]);
        }
        return $this->setData(self::FAILED_IMPORTS, $all);
    }

    /**
     * Get the total failed-import count across all batches.
     *
     * @return int
     */
    public function getFailedImportsCount(): int
    {
        return array_sum($this->getData(self::FAILED_IMPORTS) ?? []);
    }

    /**
     * Get the per-batch failed-import counts, keyed by importer ID.
     *
     * @return array<int|string, int>
     */
    public function getFailedImportsByBatch(): array
    {
        return $this->getData(self::FAILED_IMPORTS) ?? [];
    }

    /**
     * Set the total failed-message count for the job.
     *
     * @param int $count
     * @return $this
     */
    public function setFailedMessagesCount(int $count): JobDetails
    {
        return $this->setData(self::FAILED_MESSAGES, max(0, $count));
    }

    /**
     * Get the total failed-message count for the job.
     *
     * @return int
     */
    public function getFailedMessagesCount(): int
    {
        return (int) ($this->getData(self::FAILED_MESSAGES) ?? 0);
    }
}
