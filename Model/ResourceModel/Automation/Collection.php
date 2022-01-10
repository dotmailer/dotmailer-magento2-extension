<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Automation;

use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Automation::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Automation::class
        );
    }

    /**
     * @return array
     */
    public function getTypesForPendingAndConfirmedAutomations()
    {
        $collection = $this->addFieldToFilter(
            'enrolment_status',
            [
                'in' => [
                    StatusInterface::PENDING,
                    StatusInterface::CONFIRMED
                ]
            ]
        );
        $collection->getSelect()->group('automation_type');

        return $collection->getColumnValues('automation_type');
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
                    StatusInterface::PENDING,
                    StatusInterface::CONFIRMED
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
            StatusInterface::PENDING_OPT_IN
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
            StatusInterface::PENDING_OPT_IN
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
            ->addFieldToFilter(
                'automation_type',
                AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT
            );

        return $collection;
    }

    /**
     * @param string $email
     * @param string|int $websiteId
     * @return Collection
     */
    public function getSubscriberAutomationByEmail($email, $websiteId)
    {
        return $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter(
                'automation_type',
                AutomationTypeHandler::AUTOMATION_TYPE_NEW_SUBSCRIBER
            );
    }

    /**
     * @param string $email
     * @param array $updated
     * @param string|int $storeId
     * @return Collection
     */
    public function getAbandonedCartAutomationsForContactByInterval($email, $updated, $storeId)
    {
        return $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter(
                'automation_type',
                AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT
            )
            ->addFieldToFilter('updated_at', $updated);
    }

    /**
     * Search the email_automation table for jobs with automation_status = 'Failed',
     * with a created_at time inside the specified time window.
     *
     * @param array $timeWindow
     * @return $this
     */
    public function fetchAutomationEnrolmentsWithErrorStatusInTimeWindow($timeWindow)
    {
        return $this->addFieldToFilter('enrolment_status', StatusInterface::FAILED)
            ->addFieldToFilter('created_at', $timeWindow)
            ->setOrder('updated_at', 'DESC');
    }

    /**
     * Search the email_automation table for jobs with automation_status = 'pending',
     * with a created_at time inside the time window but older than 1 hour.
     *
     * @param array $timeWindow
     * @return Collection
     * @throws \Exception
     */
    public function fetchAutomationEnrolmentsWithPendingStatusInTimeWindow($timeWindow)
    {
        return $this->addFieldToFilter('enrolment_status', StatusInterface::PENDING)
            ->addFieldToFilter('created_at', $timeWindow)
            ->addFieldToFilter('created_at', ['lt' => new \DateTime('-1 hour')])
            ->setOrder('updated_at', 'DESC');
    }
}
