<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Abandoned;

use \Dotdigitalgroup\Email\Model\Sync\Automation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var \Dotdigitalgroup\Email\Model\Newsletter\SubscriberFilterer
     */
    public $subscriberFilterer;

    /**
     * Collection constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Newsletter\SubscriberFilterer $subscriberFilterer
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Newsletter\SubscriberFilterer $subscriberFilterer,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->subscriberFilterer = $subscriberFilterer;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Abandoned::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned::class
        );
    }

    /**
     * @return string
     */
    public function getLastPendingStatusCheckTime()
    {
        $collection = $this->addFieldToFilter(
            'status',
            \Dotdigitalgroup\Email\Model\Sync\Automation::CONTACT_STATUS_PENDING
        )->setOrder("updated_at")->setPageSize(1);

        return $collection->getFirstItem()->getUpdatedAt();
    }

    /**
     * @return $this
     */
    public function getCollectionByPendingStatus()
    {
        return $this->addFieldToFilter(
            'status',
            \Dotdigitalgroup\Email\Model\Sync\Automation::CONTACT_STATUS_PENDING
        );
    }

    /**
     * @param int $storeId
     * @param boolean $guest
     *
     * @return Collection
     */
    public function getCollectionByConfirmedStatus($storeId, $guest = false)
    {
        $collection =  $this->addFieldToFilter('status', Automation::CONTACT_STATUS_CONFIRMED)
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('abandoned_cart_number', '1');

        if ($guest) {
            $collection->addFieldToFilter('customer_id', ['null' => true]);
        } else {
            $collection->addFieldToFilter('customer_id', ['notnull' => true]);
        }

        return $collection;
    }

    /**
     * @param $number
     * @param $storeId
     * @param $updated
     * @param $status
     * @param bool $isOnlySubscribersForAC
     * @param bool $guest
     *
     * @return Collection
     */
    public function getAbandonedCartsForStore(
        $number,
        $storeId,
        $updated,
        $status,
        $isOnlySubscribersForAC,
        $guest = false
    ) {
        $abandonedCollection = $this->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('abandoned_cart_number', $number)
            ->addFieldToFilter('main_table.store_id', $storeId)
            ->addFieldToFilter('quote_updated_at', $updated)
            ->addFieldToFilter('status', $status);

        if ($guest) {
            $abandonedCollection->addFieldToFilter('main_table.customer_id', ['null' => true]);
        } else {
            $abandonedCollection->addFieldToFilter('main_table.customer_id', ['notnull' => true]);
        }

        if ($isOnlySubscribersForAC) {
            $abandonedCollection = $this->subscriberFilterer->filterBySubscribedStatus($this, "email");
        }

        return $abandonedCollection;
    }
}
