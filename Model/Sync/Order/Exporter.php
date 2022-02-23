<?php

namespace Dotdigitalgroup\Email\Model\Sync\Order;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\OrderFactory as ConnectorOrderFactory;
use Dotdigitalgroup\Email\Model\OrderFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection as OrderCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as SalesOrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Exporter
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConnectorOrderFactory
     */
    private $connectorOrderFactory;

    /**
     * @var array
     */
    private $selectedStatus = [];

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SalesOrderCollectionFactory
     */
    private $salesOrderCollectionFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ConnectorOrderFactory $connectorOrderFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Logger $logger
     * @param SalesOrderCollectionFactory $salesOrderCollectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Data $helper,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig,
        ConnectorOrderFactory $connectorOrderFactory,
        OrderCollectionFactory $orderCollectionFactory,
        Logger $logger,
        SalesOrderCollectionFactory $salesOrderCollectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->connectorOrderFactory = $connectorOrderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
    }

    /**
     * Export orders.
     *
     * @param array $orderIds
     * @return array|array[]|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function exportOrders($orderIds)
    {
        $orderCollection = $this->orderCollectionFactory
            ->create()
            ->getOrdersFromIds($orderIds);

        if (!$orderCollection->getSize()) {
            return [];
        }

        $filteredCollection = $this->filterOrderCollectionByStatus($orderCollection);
        $salesOrderCollection = $this->loadSalesOrderCollection(
            $filteredCollection->getColumnValues('order_id')
        );

        return $this->mapOrderData($salesOrderCollection);
    }

    /**
     * Filter collection.
     *
     * @param OrderCollection $collection
     *
     * @return OrderCollection
     */
    private function filterOrderCollectionByStatus($collection)
    {
        foreach ($collection as $key => $order) {
            $storeId = $order->getStoreId();
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $statuses = $this->getSelectedOrderStatuses($websiteId);

            if (!in_array($order->getOrderStatus(), $statuses)) {
                $collection->removeItemByKey($key);
            }
        }

        return $collection;
    }

    /**
     * Load sales collection.
     *
     * @param array $orderIds
     *
     * @return SalesOrderCollection
     */
    private function loadSalesOrderCollection(array $orderIds)
    {
        return $this->salesOrderCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => $orderIds]);
    }

    /**
     * Map order data.
     *
     * @param SalesOrderCollection $salesOrderCollection
     *
     * @return array|array[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function mapOrderData($salesOrderCollection)
    {
        $orders = [];
        foreach ($salesOrderCollection as $order) {
            if ($order->getId()) {
                $websiteId = $order->getStore()->getWebsiteId();

                try {
                    $connectorOrder = $this->connectorOrderFactory->create()
                        ->setOrderData($order);

                    if (array_key_exists($websiteId, $orders)) {
                        $orders[$websiteId][$order->getId()] = $this->expose($connectorOrder);
                    } else {
                        $orders += [$websiteId => [$order->getId() => $this->expose($connectorOrder)]];
                    }

                } catch (\Exception $exception) {
                    $this->logger->debug(
                        sprintf(
                            'Order id %s was not exported, but will be marked as processed.',
                            $order->getId()
                        ),
                        [(string) $exception]
                    );
                }
            }
        }

        return $orders;
    }

    /**
     * Order status config value.
     *
     * @param string|int $websiteId
     *
     * @return array|bool
     */
    private function getSelectedOrderStatuses($websiteId)
    {
        if (!isset($this->selectedStatus[$websiteId])) {
            $this->selectedStatus[$websiteId] = $status = $this->scopeConfig->getValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            );
        } else {
            $status = $this->selectedStatus[$websiteId];
        }

        if ($status) {
            return explode(',', $status);
        } else {
            return false;
        }
    }

    /**
     * Expose.
     *
     * @param \Dotdigitalgroup\Email\Model\Connector\Order $connectorOrder
     * @return array
     */
    private function expose($connectorOrder)
    {
        $properties = get_object_vars($connectorOrder);

        //remove null/0/false values
        return array_filter($properties);
    }
}
