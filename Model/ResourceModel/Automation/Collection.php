<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Automation;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Automation',
            'Dotdigitalgroup\Email\Model\ResourceModel\Automation'
        );
    }

    /**
     * Get automation status type
     *
     * @return array
     */
    public function getAutomationStatusType()
    {
        $automationOrderStatusCollection = $this->addFieldToFilter(
                'enrolment_status',
            \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING
            );
        $automationOrderStatusCollection
            ->addFieldToFilter(
                'automation_type',
                ['like' => '%' . \Dotdigitalgroup\Email\Model\Sync\Automation::ORDER_STATUS_AUTOMATION . '%']
            )->getSelect()
            ->group('automation_type');

        return $automationOrderStatusCollection->getColumnValues('automation_type');
    }

    /**
     * Get collection by type
     *
     * @param $type
     * @param $limit
     * @return $this
     */
    public function getCollectionByType($type, $limit)
    {
        $collection = $this->addFieldToFilter(
            'enrolment_status',
            \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING
        )->addFieldToFilter(
            'automation_type',
            $type
        );
        //limit because of the each contact request to get the id
        $collection = $collection->getSelect()->limit($limit);

        return $collection;
    }
}
