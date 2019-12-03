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
}
