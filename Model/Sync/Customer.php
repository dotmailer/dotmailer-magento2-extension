<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MergeManager;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Model\Sync\Customer\ExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Customer\ListCohortResolver;
use Dotdigitalgroup\Email\Model\Sync\Export\ExporterInterface;
use Http\Client\Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Customer extends DataObject implements SyncInterface
{
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
     * @var ExporterFactory
     */
    private $exporterFactory;

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
     * @var RegistrationStoreResolver
     */
    private $registrationStoreResolver;

    /**
     * @var int
     */
    private $totalCustomersSyncedCount = 0;

    /**
     * Customer sync constructor.
     *
     * @param Data $helper
     * @param Logger $logger
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param MegaBatchProcessorFactory $megaBatchProcessorFactory
     * @param MergeManager $mergeManager
     * @param ExporterFactory $exporterFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ListCohortResolver $listCohortResolver
     * @param RegistrationStoreResolver $registrationStoreResolver
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ContactCollectionFactory $contactCollectionFactory,
        MegaBatchProcessorFactory $megaBatchProcessorFactory,
        MergeManager $mergeManager,
        ExporterFactory $exporterFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ListCohortResolver $listCohortResolver,
        RegistrationStoreResolver $registrationStoreResolver,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->megaBatchProcessorFactory = $megaBatchProcessorFactory;
        $this->mergeManager = $mergeManager;
        $this->exporterFactory = $exporterFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->listCohortResolver = $listCohortResolver;
        $this->registrationStoreResolver = $registrationStoreResolver;
        parent::__construct($data);
    }

    /**
     * Customer sync.
     *
     * @param \DateTime|null $from
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(?\DateTime $from = null)
    {
        $start = microtime(true);
        $megaBatchSize = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT);
        $breakValue = $this->isRunFromDeveloperButton() ?
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_LIMIT):
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE);

        foreach ($this->storeManager->getWebsites() as $website) {
            $apiEnabled = $this->helper->isEnabled($website->getId());
            $customerSyncEnabled = $this->helper->isCustomerSyncEnabled($website->getId());

            if (!$apiEnabled || !$customerSyncEnabled) {
                continue;
            }

            try {
                $megaBatch = [];
                $megaBatchCount = 0;

                foreach ($this->listCohortResolver->resolve($website) as $cohort) {
                    if ($breakValue && $this->totalCustomersSyncedCount >= $breakValue) {
                        break;
                    }

                    $this->loopByWebsite(
                        $website,
                        $megaBatchSize,
                        $breakValue,
                        $cohort,
                        $megaBatch,
                        $megaBatchCount
                    );
                }

                $this->processMegaBatch($megaBatch, (int) $website->getId());
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf(
                        'Error in customer sync for website id: %d',
                        $website->getId()
                    ),
                    [(string) $e]
                );
            }
        }

        $message = '----------- Customer sync ----------- : '
            . gmdate('H:i:s', (int) (microtime(true) - $start))
            . ', Total synced = ' . $this->totalCustomersSyncedCount;

        if ($this->totalCustomersSyncedCount > 0 || $this->helper->isDebugEnabled()) {
            $this->logger->info($message);
        }

        return [
            'message' => $message,
            'syncedCustomers' => $this->totalCustomersSyncedCount
        ];
    }

    /**
     * Perform batching loop for one list cohort of a website.
     *
     * A cohort is the set of registration stores in the website that resolve to the same list.
     * The mega batch is shared by all cohorts of the website, so contacts for different lists
     * are imported together; each contact carries its own list.
     *
     * @param WebsiteInterface $website
     * @param int $megaBatchSize
     * @param int $breakValue
     * @param array $cohort
     * @param array $megaBatch
     * @param int $megaBatchCount
     *
     * @return void
     * @throws Exception
     * @throws LocalizedException
     */
    private function loopByWebsite(
        WebsiteInterface $website,
        int $megaBatchSize,
        int $breakValue,
        array $cohort,
        array &$megaBatch,
        int &$megaBatchCount
    ) {
        $listId = (int) $cohort['listId'];
        $websiteStoreIds = $this->registrationStoreResolver->getWebsiteStoreIds((int) $website->getId());
        $offset = 0;
        $loopStart = true;
        $limit = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );

        /** @var ExporterInterface $exporter */
        $exporter = $this->exporterFactory->create();
        $exporter->setFieldMapping($website);

        do {
            $customerIds = $this->getCustomerIdsToSync($website->getId(), $cohort, $websiteStoreIds, $limit, $offset);
            $customerIdCount = count($customerIds);
            if ($customerIdCount === 0) {
                break;
            }

            // offset is not always the same as batch count
            $offset += $customerIdCount;

            if ($loopStart) {
                $this->logger->info(
                    sprintf(
                        '%s sync: syncing website %d, list %d',
                        Importer::IMPORT_TYPE_CUSTOMER,
                        $website->getId(),
                        $listId
                    )
                );
                $loopStart = false;
            }

            $batch = $exporter->export($customerIds, $website, $listId);
            $batchCount = count($batch);
            if ($batchCount === 0) {
                $this->logger->debug(
                    sprintf(
                        'Could not batch customers in website %d. Tried exporting %d ids from %d to %d. Continuing.',
                        $website->getId(),
                        $limit,
                        min($customerIds),
                        max($customerIds)
                    )
                );
                continue;
            }

            $megaBatch = $this->mergeManager->mergeBatch($batch, $megaBatch);

            $megaBatchCount += $batchCount;
            $this->totalCustomersSyncedCount += $batchCount;

            if ($megaBatchCount >= $megaBatchSize) {
                $this->processMegaBatch($megaBatch, (int) $website->getId());
                $megaBatch = [];
                $megaBatchCount = 0;
                $offset = 0;
            }
        } while (!$breakValue || $this->totalCustomersSyncedCount < $breakValue);
    }

    /**
     * Process a mega batch of customer contacts for a website.
     *
     * @param array $megaBatch
     * @param int $websiteId
     *
     * @return void
     */
    private function processMegaBatch(array $megaBatch, int $websiteId): void
    {
        $this->megaBatchProcessorFactory->create()
            ->process(
                $megaBatch,
                $websiteId,
                Importer::IMPORT_TYPE_CUSTOMER
            );
    }

    /**
     * Get customer ids to sync for a list cohort.
     *
     * @param int|string $websiteId
     * @param array $cohort
     * @param array $websiteStoreIds
     * @param int $pageSize
     * @param int $offset
     *
     * @return array
     */
    private function getCustomerIdsToSync($websiteId, array $cohort, array $websiteStoreIds, $pageSize, $offset = 0)
    {
        return $this->contactCollectionFactory->create()
            ->getCustomersToImportByRegistrationStores(
                $websiteId,
                $cohort['storeIds'],
                $websiteStoreIds,
                $cohort['includeUnassigned'],
                $cohort['onlySubscribers'],
                $pageSize,
                $offset
            )->getColumnValues('customer_id');
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
