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
     * @param CollectionFactory $cronCollection
     */
    public function __construct(
        CollectionFactory $cronCollection
    ) {
        $this->cronCollection = $cronCollection;
    }

    /**
     * Check if a cron job has already run at the same time.
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

        return (bool) $currentRunningJob->getSize();
    }

    /**
     * Get last job finished at.
     *
     * @param string $jobCode
     *
     * @return string
     */
    public function getLastJobFinishedAt($jobCode)
    {
        /** @var \Magento\Cron\Model\Schedule $lastSuccessfulJob */
        $lastSuccessfulJob = $this->cronCollection->create()
            ->addFieldToFilter('job_code', $jobCode)
            ->addFieldToFilter('status', 'success')
            ->setOrder('finished_at', 'desc')
            ->getFirstItem();

        return $lastSuccessfulJob->getFinishedAt();
    }
}
