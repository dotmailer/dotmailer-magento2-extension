<?php

namespace Dotdigitalgroup\Email\Model\Cron;

use Dotdigitalgroup\Email\Model\ResourceModel\Cron\CollectionFactory;

class JobChecker
{
    /**
     * @var CollectionFactory
     */
    private $cronCollection;

    /**
     * JobChecker constructor.
     * @param CollectionFactory $cronCollection
     */
    public function __construct(
        CollectionFactory $cronCollection
    ) {
        $this->cronCollection = $cronCollection;
    }

    /**
     * Check if a cron job has already run at the same time
     *
     * @param string $jobCode
     * @return bool
     */
    public function hasAlreadyBeenRun($jobCode)
    {
        $currentRunningJob = $this->cronCollection->create()
            ->addFieldToFilter('job_code', $jobCode)
            ->addFieldToFilter('status', 'running')
            ->setPageSize(1);

        if ($currentRunningJob->getSize()) {
            $jobOfSameTypeAndScheduledAtDateAlreadyExecuted = $this->cronCollection->create()
                ->addFieldToFilter('job_code', $jobCode)
                ->addFieldToFilter('scheduled_at', $currentRunningJob->getFirstItem()->getScheduledAt())
                ->addFieldToFilter('status', ['in' => ['success', 'failed']]);

            return ($jobOfSameTypeAndScheduledAtDateAlreadyExecuted->getSize()) ? true : false;
        }

        return false;
    }
}
