<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Order;

use Dotdigitalgroup\Email\Api\Model\Sync\Export\InsightDataExporterInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\Order;
use Dotdigitalgroup\Email\Model\Connector\OrderFactory as ConnectorOrderFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection as OrderCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkOrderRecordCollectionBuilderFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\Collection as SalesOrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Exporter implements InsightDataExporterInterface
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
     * @var SdkOrderRecordCollectionBuilderFactory
     */
    private $sdkOrderRecordCollectionBuilderFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ConnectorOrderFactory $connectorOrderFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Logger $logger
     * @param SalesOrderCollectionFactory $salesOrderCollectionFactory
     * @param SdkOrderRecordCollectionBuilderFactory $sdkOrderRecordCollectionBuilderFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ConnectorOrderFactory $connectorOrderFactory,
        OrderCollectionFactory $orderCollectionFactory,
        Logger $logger,
        SalesOrderCollectionFactory $salesOrderCollectionFactory,
        SdkOrderRecordCollectionBuilderFactory $sdkOrderRecordCollectionBuilderFactory
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->connectorOrderFactory = $connectorOrderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
        $this->sdkOrderRecordCollectionBuilderFactory = $sdkOrderRecordCollectionBuilderFactory;
    }

    /**
     * Export orders.
     *
     * @param array $data
     * @return array|array[]
     * @throws NoSuchEntityException
     */
    public function export(array $data): array
    {
        $orderCollection = $this->orderCollectionFactory
            ->create()
            ->getOrdersFromIds($data);

        if (!$orderCollection->getSize()) {
            return [];
        }

        $filteredCollection = $this->filterOrderCollectionByStatus($orderCollection);
        $salesOrderCollection = $this->loadSalesOrderCollection(
            $filteredCollection->getColumnValues('order_id')
        );

        return $this->sdkOrderRecordCollectionBuilderFactory->create()
            ->setBuildableData($salesOrderCollection)
            ->build()
            ->all();
    }

    /**
     * Map order data.
     *
     * This method maps the order data from the given sales order collection.
     * It iterates through each order in the collection, processes it, and
     * organizes the data into an array structure.
     *
     * @param SalesOrderCollection $salesOrderCollection The collection of sales orders to be mapped.
     *
     * @return array|array[] Returns an array of mapped order data.
     * @throws NoSuchEntityException If an entity does not exist.
     * @todo Remove this method in the future and update order automation.
     *
     * @deprecated This method is deprecated because the SdkOrderRecordCollectionBuilderFactory
     * provides a more efficient and flexible way to build order records.
     * @see SdkOrderRecordCollectionBuilderFactory
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
                            "Order id %s was not exported, but will be marked as processed in the context of a sync",
                            $order->getId()
                        ),
                        [$exception, $exception->errors()]
                    );
                } catch (Exception $exception) {
                    $this->logger->debug(
                        sprintf(
                            "Order id %s was not exported, but will be marked as processed in the context of a sync",
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
     * @throws NoSuchEntityException
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
