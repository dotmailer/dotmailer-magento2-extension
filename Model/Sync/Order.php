<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use DateTime;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchMergerInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Order\ExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Exception;
use Dotdigitalgroup\Email\Model\Importer;
use Magento\Store\Model\Website;

class Order extends DataObject implements SyncInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ExporterFactory
     */
    private $exporterFactory;

    /**
     * @var MegaBatchProcessorFactory
     */
    private $megaBatchProcessorFactory;

    /**
     * @var OrderResourceFactory
     */
    private $orderResourceFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var BatchMergerInterface
     */
    private $mergeManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var int
     */
    private $totalOrdersProcessedCount = 0;

    /**
     * @var int
     */
    private $totalOrdersSyncedCount = 0;

    /**
     * Order constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param ExporterFactory $exporterFactory
     * @param MegaBatchProcessorFactory $megaBatchProcessorFactory
     * @param OrderResourceFactory $orderResourceFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param BatchMergerInterface $mergeManager
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Data $helper,
        ExporterFactory $exporterFactory,
        MegaBatchProcessorFactory $megaBatchProcessorFactory,
        OrderResourceFactory $orderResourceFactory,
        OrderCollectionFactory $orderCollectionFactory,
        BatchMergerInterface $mergeManager,
        Logger $logger,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->exporterFactory = $exporterFactory;
        $this->megaBatchProcessorFactory = $megaBatchProcessorFactory;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->mergeManager = $mergeManager;
        $this->logger = $logger;
        parent::__construct($data);
    }

    /**
     * Sync orders.
     *
     * @param DateTime|null $from Optional start date for syncing orders.
     * @return array Returns an array with a message and the total number of synced orders.
     * @throws \Http\Client\Exception
     */
    public function sync(DateTime $from = null): array
    {
        $start = microtime(true);
        foreach ($this->storeManager->getWebsites() as $website) {
            try {
                $this->processWebsiteOrders($website);
            } catch (Exception $e) {
                $this->logger->error(
                    sprintf('Error in order sync for website id: %d', $website->getId()),
                    [(string)$e]
                );
            }
        }

        $message = '----------- Order sync ----------- : ' .
            gmdate('H:i:s', (int) (microtime(true) - $start)) .
            ', Total processed = ' . $this->totalOrdersProcessedCount .
            ', Total synced = ' .$this->totalOrdersSyncedCount;

        if ($this->totalOrdersSyncedCount > 0 || $this->helper->isDebugEnabled()) {
            $this->logger->info($message);
        }

        return ['message' => $message, 'syncedOrders' => $this->totalOrdersSyncedCount];
    }

    /**
     * Process orders for a specific website.
     *
     * @param Website $website The website for which to process orders.
     * @throws \Http\Client\Exception
     * @throws LocalizedException
     */
    private function processWebsiteOrders(WebsiteInterface $website)
    {
        $limit = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT);
        $megaBatchSize = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_ORDERS);
        $breakValue = $this->isRunFromDeveloperButton()
            ? $limit
            : (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE);

        if (!$this->shouldSyncWebsite($website, $breakValue)) {
            return;
        }

        $megaBatch = [];
        $megaBatchCount = 0;
        $loopStart = true;
        $storeIds = $website->getStoreIds();

        $exporter = $this->exporterFactory->create();

        do {
            $ordersToProcess = $this->orderCollectionFactory->create()
                ->getOrdersToProcess($limit, $storeIds);

            if (empty($ordersToProcess)) {
                break;
            }

            if ($loopStart) {
                $this->logger->info(
                    sprintf('Order sync: syncing website %d', $website->getId())
                );
                $loopStart = false;
            }

            $batch = $exporter->export($ordersToProcess);
            $megaBatch = $this->mergeManager->mergeBatch($batch, $megaBatch);

            $this->totalOrdersProcessedCount += count($ordersToProcess);
            $megaBatchCount += count($batch);
            $this->totalOrdersSyncedCount += count($batch);

            $this->orderResourceFactory->create()
                ->setProcessed($ordersToProcess);

            $this->logger->info(
                sprintf(
                    'Order sync: %s orders processed, %s selected',
                    count($ordersToProcess),
                    count($batch)
                )
            );

            if ($megaBatchCount >= $megaBatchSize) {
                $this->megaBatchProcessorFactory->create()
                    ->process(
                        $megaBatch,
                        (int) $website->getId(),
                        Importer::IMPORT_TYPE_ORDERS
                    );
                $megaBatch = [];
                $megaBatchCount = 0;
            }
        } while (!$breakValue || $this->totalOrdersSyncedCount < $breakValue);

        $this->megaBatchProcessorFactory->create()
            ->process(
                $megaBatch,
                (int) $website->getId(),
                Importer::IMPORT_TYPE_ORDERS
            );
    }

    /**
     * Check if the website should be synced.
     *
     * @param WebsiteInterface $website The website to check.
     * @param int|null $breakValue The break value to determine if syncing should stop.
     * @return bool Returns true if the website should be synced, false otherwise.
     */
    private function shouldSyncWebsite(WebsiteInterface $website, ?int $breakValue): bool
    {
        return $this->helper->isEnabled($website->getId())
            && $this->helper->isOrderSyncEnabled($website->getId())
            && (!$breakValue || $this->totalOrdersSyncedCount < $breakValue);
    }

    /**
     * Determines whether the sync was triggered from Configuration > Dotdigital > Developer > Sync Settings.
     *
     * @return bool
     */
    private function isRunFromDeveloperButton(): bool
    {
        return (bool)$this->_getData('web');
    }
}
