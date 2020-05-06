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
     */
    public function _construct()
    {
        $this->_init(\Magento\Cron\Model\Schedule::class, \Magento\Cron\Model\ResourceModel\Schedule::class);
    }

    /**
     * Search the cron_schedule table for jobs with error status,
     * with a scheduled_at time inside the specified time window.
     *
     * @param array $timeWindow
     * @return $this
     */
    public function fetchCronTasksWithErrorStatusInTimeWindow($timeWindow)
    {
        return $this->addFieldToSelect(['job_code', 'messages', 'scheduled_at'])
            ->addFieldToFilter('job_code', ['like' => "%ddg_automation%"])
            ->addFieldToFilter('status', 'error')
            ->addFieldToFilter('scheduled_at', $timeWindow)
            ->setOrder('scheduled_at', 'DESC');
    }
}
