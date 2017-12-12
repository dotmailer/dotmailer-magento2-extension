<?php


namespace Dotdigitalgroup\Email\Model\ResourceModel\Cron;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'schedule_id';

    /**
     * Initialize resource collection
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(\Magento\Cron\Model\Schedule::class, \Magento\Cron\Model\ResourceModel\Schedule::class);
    }

    /**
     * @param $jobCode
     * @return \Magento\Cron\Model\ResourceModel\Schedule\
     */
    public function getRunningJobByCode($jobCode)
    {
        return $this->addFieldToFilter('job_code', $jobCode)
            ->addFieldToFilter('status', 'running')
            ->setPageSize(1)
            ->getFirstItem();
    }

    /**
     * @param $jobCode
     * @param $scheduledAt
     *
     * @return bool
     */
    public function jobOfSameTypeAndScheduledAtDateAlreadyExecuted($jobCode, $scheduledAt)
    {
        $collection = $this->addFieldToFilter('job_code', $jobCode)
            ->addFieldToFilter('scheduled_at', $scheduledAt)
            ->addFieldToFilter('status', ['in' => ['success', 'failed']]);

        if ($collection->getSize()) {
            return true;
        }

        return false;
    }
}
