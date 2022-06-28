<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Dotdigitalgroup\Email\Model\Sync\Order\BatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Order\Exporter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Sync Orders.
 */
class Order extends DataObject implements SyncInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var OrderResourceFactory
     */
    private $orderResourceFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var BatchProcessor
     */
    private $batchProcessor;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param OrderResourceFactory $orderResourceFactory
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param BatchProcessor $batchProcessor
     * @param Exporter $exporter
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        OrderResourceFactory $orderResourceFactory,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        BatchProcessor $batchProcessor,
        Exporter $exporter,
        OrderCollectionFactory $orderCollectionFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->orderResourceFactory = $orderResourceFactory;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->batchProcessor = $batchProcessor;
        $this->exporter = $exporter;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->storeManager = $storeManager;

        parent::__construct($data);
    }

    /**
     * Sync process.
     *
     * @param \DateTime|null $from
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(\DateTime $from = null)
    {
        $start = microtime(true);
        $limit = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );

        $breakValue = $this->isRunFromDeveloperButton() ? $limit : $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE
        );

        $megaBatchSize = $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_ORDERS);
        $megaBatch = [];
        $totalOrdersSyncedCount = 0;
        $megaBatchCount = 0;
        $loopStart = true;
        $ordersProcessedCount = 0;
        $storeIds = $this->getOrderEnabledStoreIds();

        do {
            $ordersToProcess = $this->orderCollectionFactory
                ->create()
                ->getOrdersToProcess($limit, $storeIds);

            if (!$ordersToProcess) {
                break;
            }

            if ($loopStart) {
                $this->helper->log('----------- Order sync ----------- : Start batching');
                $loopStart = false;
            }

            $megaBatch = $this->mergeBatch(
                $megaBatch,
                $batch = $this->exporter->exportOrders($ordersToProcess)
            );

            $ordersProcessedCount += count($ordersToProcess);
            $megaBatchCount += $batchCount = $this->getBatchOrdersCount($batch);
            $totalOrdersSyncedCount += $batchCount;

            $this->orderResourceFactory->create()
                ->setProcessed($ordersToProcess);

            $this->helper->log(sprintf('Order sync: %s orders processed.', count($ordersToProcess)));

            if ($megaBatchCount >= $megaBatchSize) {
                $this->batchProcessor->process($megaBatch);
                $megaBatchCount = 0;
                $megaBatch = [];
            }
        } while (!$breakValue || $totalOrdersSyncedCount < $breakValue);

        if (!empty($megaBatch)) {
            $this->batchProcessor->process($megaBatch);
        }

        $message = '----------- Order sync ----------- : ' .
            gmdate('H:i:s', (int) (microtime(true) - $start)) .
            ', Total processed = ' . $ordersProcessedCount . ', Total synced = ' . $totalOrdersSyncedCount;

        if ($totalOrdersSyncedCount > 0 || $this->helper->isDebugEnabled()) {
            $this->helper->log($message);
        }

        return [
            'message' => $message,
            'syncedOrders' => $totalOrdersSyncedCount
        ];
    }

    /**
     * Creates the order mega batch.
     *
     * @param array $megaBatch
     * @param array $batch
     * @return array
     */
    private function mergeBatch(array $megaBatch, array $batch)
    {
        foreach ($batch as $websiteId => $orders) {
            if (array_key_exists($websiteId, $megaBatch)) {
                foreach ($orders as $order) {
                    $megaBatch[$websiteId][] = $order;
                }
            } else {
                $megaBatch += [$websiteId => $orders];
            }
        }

        return $megaBatch;
    }

    /**
     * Determines whether the sync was triggered from Configuration > Dotdigital > Developer > Sync Settings.
     *
     * @return bool
     */
    private function isRunFromDeveloperButton()
    {
        return (bool)$this->_getData('web');
    }

    /**
     * Order counter.
     *
     * @param array $batch
     * @return int
     */
    private function getBatchOrdersCount(array $batch)
    {
        $ordersToSync = 0;

        foreach ($batch as $orders) {
            $ordersToSync += count($orders);
        }

        return $ordersToSync;
    }

    /**
     * Get all stores that order sync is enabled.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrderEnabledStoreIds()
    {
        $websites = $this->storeManager->getWebsites(true);

        $storeIds = [];
        /** @var \Magento\Store\Model\Website $website */
        foreach ($websites as $website) {
            $apiEnabled = $this->helper->isEnabled($website->getId());
            // api and order sync should be enabled, skip website with no store ids
            if ($apiEnabled && $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $website->getId()
            )
            ) {
                foreach ($website->getStoreIds() as $storeId) {
                    $storeIds[] = $storeId;
                }
            }
        }

        return $storeIds;
    }
}
