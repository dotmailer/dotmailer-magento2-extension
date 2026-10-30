<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Api\Model\Sync\Export\ContactExporterInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MergeManager;
use Dotdigitalgroup\Email\Model\Sync\Customer\ListCohortResolver;
use Dotdigitalgroup\Email\Model\Sync\Export\ExporterInterface;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\OrderHistoryChecker;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporterFactory;
use Http\Client\Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Subscriber extends DataObject implements SyncInterface
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
     * @var MegaBatchProcessorFactory
     */
    private $megaBatchProcessorFactory;

    /**
     * @var MergeManager
     */
    private $mergeManager;

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
     * @var ListCohortResolver
     */
    private $listCohortResolver;

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
     * @param MegaBatchProcessorFactory $megaBatchProcessorFactory
     * @param MergeManager $mergeManager
     * @param OrderHistoryChecker $orderHistoryChecker
     * @param SubscriberExporterFactory $subscriberExporterFactory
     * @param SubscriberWithSalesExporterFactory $subscriberWithSalesExporterFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ListCohortResolver $listCohortResolver
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ContactCollectionFactory $contactCollectionFactory,
        MegaBatchProcessorFactory $megaBatchProcessorFactory,
        MergeManager $mergeManager,
        OrderHistoryChecker $orderHistoryChecker,
        SubscriberExporterFactory $subscriberExporterFactory,
        SubscriberWithSalesExporterFactory $subscriberWithSalesExporterFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ListCohortResolver $listCohortResolver,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->megaBatchProcessorFactory = $megaBatchProcessorFactory;
        $this->mergeManager = $mergeManager;
        $this->orderHistoryChecker = $orderHistoryChecker;
        $this->subscriberExporterFactory = $subscriberExporterFactory;
        $this->subscriberWithSalesExporterFactory = $subscriberWithSalesExporterFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->listCohortResolver = $listCohortResolver;
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
    public function sync(?\DateTime $from = null)
    {
        $start = microtime(true);
        $this->megaBatchSize = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT);
        $breakValue = $this->isRunFromDeveloperButton() ?
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_LIMIT):
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE);

        foreach ($this->storeManager->getWebsites() as $website) {
            $apiEnabled = $this->helper->isEnabled($website->getId());
            $subscriberSyncEnabled = $this->helper->isSubscriberSyncEnabled($website->getId());

            if ($apiEnabled &&
                $subscriberSyncEnabled &&
                (!$breakValue || $this->totalSubscribersSyncedCount < $breakValue)
            ) {
                try {
                    $this->loopByWebsite(
                        $website,
                        $breakValue,
                        $this->listCohortResolver->resolveSubscribers($website)
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
     * Subscribers are synced one list cohort at a time. A list cohort is the set of subscriber stores
     * in the website that resolve to the same subscriber list, so stores inheriting one list stay together.
     * Mega batches are shared across list cohorts; each contact carries its own list.
     *
     * @param WebsiteInterface $website
     * @param int $breakValue
     * @param array $listCohorts
     *
     * @return void
     * @throws LocalizedException
     */
    private function loopByWebsite(WebsiteInterface $website, int $breakValue, array $listCohorts)
    {
        $this->subscribersMegaBatch = [];
        $this->subscribersWithSalesMegaBatch = [];
        $this->subscribersMegaBatchCount = 0;
        $this->subscribersWithSalesMegaBatchCount = 0;
        $this->cohorts = [];

        /** @var ExporterInterface $subscriberExporter */
        $subscriberExporter = $this->subscriberExporterFactory->create();
        /** @var ExporterInterface $subscriberWithSalesExporter */
        $subscriberWithSalesExporter = $this->subscriberWithSalesExporterFactory->create();

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

        foreach ($listCohorts as $listCohort) {
            if ($breakValue && $this->totalSubscribersSyncedCount >= $breakValue) {
                break;
            }

            $this->subscribersLoopStart = true;
            $this->subscribersWithSalesLoopStart = true;

            $this->loopByListCohort(
                $website,
                $breakValue,
                $limit,
                $isSubscriberSalesDataEnabled,
                $listCohort,
                $subscriberExporter,
                $subscriberWithSalesExporter
            );
        }

        $this->processMegaBatches($website);
    }

    /**
     * Perform batching loop for one list cohort of a website.
     *
     * @param WebsiteInterface $website
     * @param int $breakValue
     * @param int $limit
     * @param int $isSubscriberSalesDataEnabled
     * @param array $listCohort
     * @param ExporterInterface|ContactExporterInterface $subscriberExporter
     * @param ExporterInterface|ContactExporterInterface $subscriberWithSalesExporter
     *
     * @return void
     * @throws LocalizedException
     */
    private function loopByListCohort(
        WebsiteInterface $website,
        int $breakValue,
        int $limit,
        int $isSubscriberSalesDataEnabled,
        array $listCohort,
        $subscriberExporter,
        $subscriberWithSalesExporter
    ) {
        $offset = 0;
        $listId = (int) $listCohort['listId'];
        $listCohortBatchCounts = [
            self::COHORT_SUBSCRIBERS => 0,
            self::COHORT_SUBSCRIBERS_WITH_SALES => 0
        ];

        do {
            $subscribersToSync = $this->getSubscribers($listCohort['storeIds'], $limit, $offset);
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

                    $processed = $this->exportAndBatch(
                        $cohortName,
                        $cohort['contacts'],
                        $exporter,
                        $website,
                        $listId,
                        $listCohortBatchCounts[$cohortName]
                    );

                    $offset -= $processed;
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'Error in subscriber sync for website id: %d, list id: %d, cohort: %s',
                            $website->getId(),
                            $listId,
                            $cohortName
                        ),
                        [(string) $e]
                    );
                }
            }
        } while (!$breakValue || $this->totalSubscribersSyncedCount < $breakValue);
    }

    /**
     * Import and reset the remaining mega batches of the website.
     *
     * @param WebsiteInterface $website
     *
     * @return void
     */
    private function processMegaBatches(WebsiteInterface $website)
    {
        foreach (array_keys($this->cohorts) as $cohortName) {
            $prettyCohortName = $this->getPrettyCohortName($cohortName);
            $megaBatch = $prettyCohortName . 'MegaBatch';
            $megaBatchCount = $prettyCohortName . 'MegaBatchCount';

            $this->megaBatchProcessorFactory->create()
                ->process(
                    $this->$megaBatch,
                    (int) $website->getId(),
                    Importer::IMPORT_TYPE_SUBSCRIBERS
                );
            $this->$megaBatch = [];
            $this->$megaBatchCount = 0;
        }
    }

    /**
     * Set columns, export subscribers and batch.
     *
     * @param string $cohortName
     * @param array $subscribers
     * @param ContactExporterInterface $exporter
     * @param WebsiteInterface $website
     * @param int $listId
     * @param int $listCohortBatchCount
     *
     * @return int
     * @throws LocalizedException
     */
    private function exportAndBatch(
        string $cohortName,
        array $subscribers,
        ContactExporterInterface $exporter,
        $website,
        int $listId,
        int &$listCohortBatchCount
    ) {
        $processed = 0;

        if (empty($exporter->getFieldMapping())) {
            $exporter->setFieldMapping($website);
        }

        $batch = $exporter->export($subscribers, $website, $listId);
        $batchCount = count($batch);
        if ($batchCount === 0) {
            return 0;
        }

        $prettyCohortName = $this->getPrettyCohortName($cohortName);
        $megaBatch = $prettyCohortName.'MegaBatch';
        $megaBatchCount = $prettyCohortName.'MegaBatchCount';
        $loopStart = $prettyCohortName.'LoopStart';

        if ($this->$loopStart) {
            $this->logger->info(
                sprintf(
                    '%s sync: syncing website %d, list %d',
                    $prettyCohortName,
                    $website->getId(),
                    $listId
                )
            );
            $this->$loopStart = false;
        }

        $this->$megaBatch = $this->mergeManager->mergeBatch($batch, $this->$megaBatch);
        $this->$megaBatchCount += $batchCount;
        $listCohortBatchCount += $batchCount;
        $this->totalSubscribersSyncedCount += $batchCount;

        if ($this->$megaBatchCount >= $this->megaBatchSize) {
            $this->megaBatchProcessorFactory->create()
                ->process(
                    $this->$megaBatch,
                    (int) $website->getId(),
                    Importer::IMPORT_TYPE_SUBSCRIBERS
                );
            // Only contacts from the current list affect its query offset.
            $processed = $listCohortBatchCount;
            $listCohortBatchCount = 0;
            $this->$megaBatch = [];
            $this->$megaBatchCount = 0;
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

    /**
     * Determines whether the sync was triggered from Configuration > Dotdigital > Developer > Sync Settings.
     *
     * @return bool
     */
    private function isRunFromDeveloperButton()
    {
        return (bool)$this->_getData('web');
    }
}
