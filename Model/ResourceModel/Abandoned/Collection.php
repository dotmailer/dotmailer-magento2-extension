<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Abandoned;

use Dotdigitalgroup\Email\Model\Sync\Automation;
use Dotdigitalgroup\Email\Model\StatusInterface;

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
        ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        ?\Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->subscriberFilterer = $subscriberFilterer;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Initialize resource collection.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Abandoned::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned::class
        );
    }

    /**
     * Get last pending status check time.
     *
     * @return string
     */
    public function getLastPendingStatusCheckTime()
    {
        $collection = $this->addFieldToFilter(
            'status',
            StatusInterface::PENDING_OPT_IN
        )->setOrder("updated_at")->setPageSize(1);

        return $collection->getFirstItem()->getUpdatedAt();
    }

    /**
     * Get collection by bending status.
     *
     * @return $this
     */
    public function getCollectionByPendingStatus()
    {
        return $this->addFieldToFilter(
            'status',
            StatusInterface::PENDING_OPT_IN
        );
    }

    /**
     * Get collection by confirmed status.
     *
     * @param int $storeId
     * @param boolean $guest
     *
     * @return Collection
     */
    public function getCollectionByConfirmedStatus($storeId, $guest = false)
    {
        $collection =  $this->addFieldToFilter('status', StatusInterface::CONFIRMED)
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
     * Get abandoned carts for store.
     *
     * @param int $number
     * @param int $storeId
     * @param array $updated
     * @param string $status
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
