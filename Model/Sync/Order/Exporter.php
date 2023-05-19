<?php

namespace Dotdigitalgroup\Email\Model\Sync\Order;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\Order;
use Dotdigitalgroup\Email\Model\Connector\OrderFactory as ConnectorOrderFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection as OrderCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\Collection as SalesOrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Exporter
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @param ScopeConfigInterface $scopeConfig
     * @param ConnectorOrderFactory $connectorOrderFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Logger $logger
     * @param SalesOrderCollectionFactory $salesOrderCollectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ConnectorOrderFactory $connectorOrderFactory,
        OrderCollectionFactory $orderCollectionFactory,
        Logger $logger,
        SalesOrderCollectionFactory $salesOrderCollectionFactory
    ) {
        $this->storeManager = $storeManager;
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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function exportOrders(array $orderIds)
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
     * Map order data.
     *
     * @param SalesOrderCollection $salesOrderCollection
     *
     * @return array|array[]
     * @throws NoSuchEntityException
     */
    public function mapOrderData($salesOrderCollection): array
    {
        $orders = [];
        foreach ($salesOrderCollection as $order) {
            if ($order->getId()) {
                $websiteId = $order->getStore()->getWebsiteId();
                try {
                    /** @var Order $connectorOrder */
                    $connectorOrder = $this->connectorOrderFactory->create();
                    $connectorOrder->setOrderData($order);
                    if (array_key_exists($websiteId, $orders)) {
                        $orders[$websiteId][$order->getIncrementId()] = $connectorOrder->toArrayWithEmptyArrayCheck();
                    } else {
                        $orders += [
                            $websiteId => [
                                $order->getIncrementId() => $connectorOrder->toArrayWithEmptyArrayCheck()
                            ]
                        ];
                    }
                } catch (SchemaValidationException $exception) {
                    $this->logger->debug(
                        sprintf(
                            "Order id %s was not exported, but will be marked as processed",
                            $order->getId()
                        ),
                        [$exception, $exception->errors()]
                    );
                } catch (Exception $exception) {
                    $this->logger->debug(
                        sprintf(
                            "Order id %s was not exported, but will be marked as processed",
                            $order->getId()
                        ),
                        [$exception]
                    );
                }
            }
        }
        return $orders;
    }

    /**
     * Filter collection.
     *
     * @param OrderCollection $collection
     *
     * @return OrderCollection
     */
    private function filterOrderCollectionByStatus(OrderCollection $collection)
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
     * Order status config value.
     *
     * @param string|int $websiteId
     *
     * @return array
     */
    private function getSelectedOrderStatuses($websiteId): array
    {
        if (!isset($this->selectedStatus[$websiteId])) {
            $this->selectedStatus[$websiteId] = $status = $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            );
        } else {
            $status = $this->selectedStatus[$websiteId];
        }

        return $status ? explode(',', $status) : [];
    }
}
