<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Automation;

use Dotdigitalgroup\Email\Model\Sync\Automation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Automation::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Automation::class
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
            Automation::AUTOMATION_STATUS_PENDING
        );
        $automationOrderStatusCollection
            ->addFieldToFilter(
                'automation_type',
                ['like' => '%' . Automation::ORDER_STATUS_AUTOMATION . '%']
            )->getSelect()
            ->group('automation_type');

        return $automationOrderStatusCollection->getColumnValues('automation_type');
    }

    /**
     * Get collection by type.
     *
     * @param string $type
     * @param string $limit
     *
     * @return $this
     */
    public function getCollectionByType($type, $limit)
    {
        $collection = $this->addFieldToFilter(
            'enrolment_status',
            [
                'in' => [
                    Automation::AUTOMATION_STATUS_PENDING,
                    Automation::CONTACT_STATUS_CONFIRMED
                ]
            ]
        )->addFieldToFilter(
            'automation_type',
            $type
        );
        //limit because of the each contact request to get the id
        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * @param boolean $expireTime
     *
     * @return $this
     */
    public function getCollectionByPendingStatus($expireTime = false)
    {
        $collection = $this->addFieldToFilter(
            'enrolment_status',
            Automation::CONTACT_STATUS_PENDING
        );
        if ($expireTime) {
            $collection->addFieldToFilter('created_at', ['lt' => $expireTime]);
        }

        return $collection;
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getLastPendingStatusCheckTime()
    {
        $collection = $this->addFieldToFilter(
            'enrolment_status',
            Automation::CONTACT_STATUS_PENDING
        )->setOrder("updated_at")->setPageSize(1);

        return $collection->getFirstItem()->getUpdatedAt();
    }

    /**
     * @param int $quoteId
     *
     * @return Collection
     */
    public function getAbandonedCartAutomationByQuoteId($quoteId)
    {
        $collection = $this->addFieldToFilter('type_id', $quoteId)
            ->addFieldToFilter('automation_type', Automation::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT);

        return $collection;
    }

    /**
     * @param string $email
     *
     * @return Collection
     */
    public function getSubscriberAutomationByEmail($email)
    {
        $collection = $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('automation_type', Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER);

        return $collection;
    }
}
