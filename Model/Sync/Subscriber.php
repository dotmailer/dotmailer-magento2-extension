<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\SubscriberBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\OrderHistoryChecker;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Subscriber extends AbstractContactSyncer implements SyncInterface
{
    private const COHORT_SUBSCRIBERS = 'subscribers';
    private const COHORT_SUBSCRIBERS_WITH_SALES = 'subscribers_with_sales';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var SubscriberBatchProcessor
     */
    private $batchProcessor;

    /**
     * @var OrderHistoryChecker
     */
    private $orderHistoryChecker;

    /**
     * @var SubscriberExporterFactory
     */
    private $subscriberExporterFactory;

    /**
     * @var SubscriberWithSalesExporterFactory
     */
    private $subscriberWithSalesExporterFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $cohorts = [];

    /**
     * @var int
     */
    private $totalSubscribersSyncedCount = 0;

    /**
     * @var int
     */
    private $megaBatchSize = 0;

    /**
     * @var array
     */
    private $subscribersMegaBatch = [];

    /**
     * @var array
     */
    private $subscribersWithSalesMegaBatch = [];

    /**
     * @var bool
     */
    private $subscribersLoopStart;

    /**
     * @var bool
     */
    private $subscribersWithSalesLoopStart;

    /**
     * @var int
     */
    private $subscribersMegaBatchCount;

    /**
     * @var int
     */
    private $subscribersWithSalesMegaBatchCount;

    /**
     * Subscriber constructor.
     *
     * @param Data $helper
     * @param Logger $logger
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param SubscriberBatchProcessor $batchProcessor
     * @param OrderHistoryChecker $orderHistoryChecker
     * @param SubscriberExporterFactory $subscriberExporterFactory
     * @param SubscriberWithSalesExporterFactory $subscriberWithSalesExporterFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ContactCollectionFactory $contactCollectionFactory,
        SubscriberBatchProcessor $batchProcessor,
        OrderHistoryChecker $orderHistoryChecker,
        SubscriberExporterFactory $subscriberExporterFactory,
        SubscriberWithSalesExporterFactory $subscriberWithSalesExporterFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->batchProcessor = $batchProcessor;
        $this->orderHistoryChecker = $orderHistoryChecker;
        $this->subscriberExporterFactory = $subscriberExporterFactory;
        $this->subscriberWithSalesExporterFactory = $subscriberWithSalesExporterFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        parent::__construct($data);
    }

    /**
     * Sync subscribers
     *
     * @param \DateTime|null $from
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(\DateTime $from = null)
    {
        $start = microtime(true);
        $this->megaBatchSize = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT);
        $breakValue = $this->isRunFromDeveloperButton() ?
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_LIMIT):
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE);

        foreach ($this->storeManager->getWebsites() as $website) {
            $apiEnabled = $this->helper->isEnabled($website->getId());
            $subscriberSyncEnabled = $this->helper->isSubscriberSyncEnabled($website->getId());
            $subscriberAddressBook = $this->helper->getSubscriberAddressBook($website->getId());

            if ($apiEnabled &&
                $subscriberSyncEnabled &&
                $subscriberAddressBook &&
                (!$breakValue || $this->totalSubscribersSyncedCount < $breakValue)
            ) {
                try {
                    $this->loopByWebsite(
                        $website,
                        $breakValue
                    );
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'Error in subscriber sync for website id: %d',
                            $website->getId()
                        ),
                        [(string) $e]
                    );
                }
            }
        }

        $message = '----------- Subscriber sync ----------- : '
            . gmdate('H:i:s', (int) (microtime(true) - $start))
            . ', Total synced = ' . $this->totalSubscribersSyncedCount;

        if ($this->totalSubscribersSyncedCount > 0 || $this->helper->isDebugEnabled()) {
            $this->logger->info($message);
        }

        return [
            'message' => $message,
            'syncedSubscribers' => $this->totalSubscribersSyncedCount
        ];
    }

    /**
     * Perform batching loop by website.
     *
     * @param WebsiteInterface $website
     * @param int $breakValue
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loopByWebsite(WebsiteInterface $website, int $breakValue)
    {
        $this->subscribersMegaBatch = [];
        $this->subscribersWithSalesMegaBatch = [];
        $this->subscribersLoopStart = true;
        $this->subscribersWithSalesLoopStart = true;
        $this->subscribersMegaBatchCount = 0;
        $this->subscribersWithSalesMegaBatchCount = 0;
        $this->cohorts = [];

        /** @var AbstractExporter $subscriberExporter */
        $subscriberExporter = $this->subscriberExporterFactory->create();
        /** @var AbstractExporter $subscriberWithSalesExporter */
        $subscriberWithSalesExporter = $this->subscriberWithSalesExporterFactory->create();

        $offset = 0;
        $isSubscriberSalesDataEnabled = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_ENABLE_SUBSCRIBER_SALES_DATA,
            ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );
        $limit = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );

        do {
            /** @var \Magento\Store\Model\Website $website */
            $subscribersToSync = $this->getSubscribers($website->getStoreIds(), $limit, $offset);
            $subscribersCount = count($subscribersToSync->getItems());
            if ($subscribersCount === 0) {
                break;
            }

            $offset += $subscribersCount;

            $this->groupSubscribersIntoCohorts($subscribersToSync, $isSubscriberSalesDataEnabled);

            foreach ($this->cohorts as $cohortName => $cohort) {
                if (empty($cohort['contacts'])) {
                    continue;
                }

                try {
                    $exporter = ($cohortName === self::COHORT_SUBSCRIBERS_WITH_SALES) ?
                        $subscriberWithSalesExporter :
                        $subscriberExporter;

                    $filename = $cohort['filename'] ?? $exporter->getCsvFileName(
                        $website->getCode(),
                        $cohortName
                    );
                    $this->cohorts[$cohortName]['filename'] = $filename;

                    $processed = $this->exportAndBatch(
                        $cohortName,
                        $cohort['contacts'],
                        $exporter,
                        $website,
                        $filename
                    );

                    $offset -= $processed;
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'Error in subscriber sync for website id: %d, cohort: %s',
                            $website->getId(),
                            $cohortName
                        ),
                        [(string) $e]
                    );
                }
            }
        } while (!$breakValue || $this->totalSubscribersSyncedCount < $breakValue);

        foreach ($this->cohorts as $cohortName => $cohort) {
            $megaBatch = $this->getPrettyCohortName($cohortName).'MegaBatch';
            $this->batchProcessor->process(
                $this->$megaBatch,
                $website->getId(),
                $cohort['filename'] ?? ''
            );
        }
    }

    /**
     * Set columns, export subscribers and batch.
     *
     * @param string $cohortName
     * @param array $subscribers
     * @param AbstractExporter $exporter
     * @param WebsiteInterface $website
     * @param string $filename
     *
     * @return int
     */
    private function exportAndBatch(string $cohortName, array $subscribers, $exporter, $website, string $filename)
    {
        $processed = 0;

        if (empty($exporter->getCsvColumns())) {
            $exporter->setCsvColumns($website);
        }

        $batch = $exporter->export($subscribers, $website);
        $batchCount = count($batch);
        if ($batchCount === 0) {
            return 0;
        }

        $prettyCohortName = $this->getPrettyCohortName($cohortName);
        $megaBatch = $prettyCohortName.'MegaBatch';
        $megaBatchCount = $prettyCohortName.'MegaBatchCount';
        $loopStart = $prettyCohortName.'LoopStart';

        if ($this->$loopStart) {
            $exporter->initialiseCsvFile($website, $exporter->getCsvColumns(), $cohortName, $filename);
            $this->$loopStart = false;
        }

        $this->$megaBatch = $this->mergeBatch($batch, $this->$megaBatch);

        $this->$megaBatchCount += $batchCount;
        $this->totalSubscribersSyncedCount += $batchCount;

        if ($this->$megaBatchCount >= $this->megaBatchSize) {
            $this->batchProcessor->process($this->$megaBatch, $website->getId(), $filename);
            $processed = $this->$megaBatchCount;
            $this->$megaBatch = [];
            $this->$megaBatchCount = 0;
            $this->$loopStart = true;
            unset($this->cohorts[$cohortName]['filename']);
        }

        return $processed;
    }

    /**
     * Get subscribers to import.
     *
     * Initially, fetch all subscribers to import. We must do it this way so that the offset works.
     *
     * @param array $storeIds
     * @param int $limit
     * @param int $offset
     *
     * @return ContactCollection
     */
    private function getSubscribers(array $storeIds, int $limit, int $offset)
    {
        return $this->contactCollectionFactory->create()
            ->getSubscribersToImportByStoreIds($storeIds, $limit, $offset);
    }

    /**
     * Group subscribers into cohorts.
     *
     * Split the subscribers to be synced into two cohorts:
     * - no_sales_data i.e. customers and guests without orders
     * - with_sales_data i.e. guests with orders
     *
     * @param ContactCollection $subscribers
     * @param bool $isSubscriberSalesDataEnabled
     *
     * @return void
     */
    private function groupSubscribersIntoCohorts($subscribers, $isSubscriberSalesDataEnabled)
    {
        $customerSubscribers = $guestSubscribers = [];

        foreach ($subscribers as $subscriber) {
            if ($subscriber->getCustomerId()) {
                $customerSubscribers[$subscriber->getId()] = $subscriber->getEmail();
                continue;
            }
            $guestSubscribers[$subscriber->getId()] = $subscriber->getEmail();
        }

        $guestsWithOrders = $isSubscriberSalesDataEnabled ?
            $this->orderHistoryChecker->checkInSales($guestSubscribers) :
            [];
        $guestsWithoutOrders = array_diff($guestSubscribers, $guestsWithOrders);
        $subscribersWithNoSalesData = $guestsWithoutOrders + $customerSubscribers;

        $this->cohorts[self::COHORT_SUBSCRIBERS]['contacts'] = $subscribersWithNoSalesData;
        $this->cohorts[self::COHORT_SUBSCRIBERS_WITH_SALES]['contacts'] = $guestsWithOrders;
    }

    /**
     * Get cohort name for use in log lines.
     *
     * @param string $name
     *
     * @return string
     */
    private function getPrettyCohortName(string $name)
    {
        $output = '';
        $i = 0;
        foreach (explode("_", $name) as $bit) {
            $output .= $i === 0 ? $bit : ucwords($bit);
            $i++;
        }
        return $output;
    }
}
