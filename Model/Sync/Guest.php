<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MergeManager;
use Dotdigitalgroup\Email\Model\Sync\Customer\ListCohortResolver;
use Dotdigitalgroup\Email\Model\Sync\Guest\GuestExporterFactory;
use Http\Client\Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class Guest extends DataObject implements SyncInterface
{
    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var int
     */
    private $totalGuestsSyncedCount = 0;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MegaBatchProcessorFactory
     */
    private $megaBatchProcessorFactory;

    /**
     * @var MergeManager
     */
    private $mergeManager;

    /**
     * @var GuestExporterFactory
     */
    private $guestExporterFactory;

    /**
     * @var ListCohortResolver
     */
    private $listCohortResolver;

    /**
     * @var RegistrationStoreResolver
     */
    private $registrationStoreResolver;

    /**
     * Guest constructor.
     *
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param MegaBatchProcessorFactory $megaBatchProcessorFactory
     * @param MergeManager $mergeManager
     * @param GuestExporterFactory $guestExporterFactory
     * @param ListCohortResolver $listCohortResolver
     * @param RegistrationStoreResolver $registrationStoreResolver
     * @param array $data
     */
    public function __construct(
        ContactCollectionFactory $contactCollectionFactory,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Logger $logger,
        MegaBatchProcessorFactory $megaBatchProcessorFactory,
        MergeManager $mergeManager,
        GuestExporterFactory $guestExporterFactory,
        ListCohortResolver $listCohortResolver,
        RegistrationStoreResolver $registrationStoreResolver,
        array $data = []
    ) {
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->megaBatchProcessorFactory = $megaBatchProcessorFactory;
        $this->mergeManager = $mergeManager;
        $this->guestExporterFactory = $guestExporterFactory;
        $this->listCohortResolver = $listCohortResolver;
        $this->registrationStoreResolver = $registrationStoreResolver;
        parent::__construct($data);
    }

    /**
     * Guest sync.
     *
     * @param \DateTime|null $from
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(?\DateTime $from = null)
    {
        $start = microtime(true);
        $megaBatchSize = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT);

        $breakValue = $this->isRunFromDeveloperButton() ?
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_LIMIT) :
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE);

        /** @var Website $website */
        foreach ($this->storeManager->getWebsites() as $website) {
            $apiEnabled = $this->helper->isEnabled($website->getId());
            $guestSyncEnabled = $this->helper->isGuestSyncEnabled($website->getId());

            if (!$apiEnabled || !$guestSyncEnabled) {
                continue;
            }

            try {
                $megaBatch = [];
                $megaBatchCount = 0;

                foreach ($this->listCohortResolver->resolveGuests($website) as $cohort) {
                    if ($breakValue && $this->totalGuestsSyncedCount >= $breakValue) {
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
                        'Error in guest sync for website id: %d',
                        $website->getId()
                    ),
                    [(string) $e]
                );
            }
        }
        $message = '----------- Guest sync ----------- : ' .
            gmdate('H:i:s', (int) (microtime(true) - $start)) .
            ', Total synced = ' . $this->totalGuestsSyncedCount;

        if ($this->totalGuestsSyncedCount) {
            $this->helper->log($message);
        }

        return ['message' => $message];
    }

    /**
     * Perform batching loop for one list cohort of a website.
     *
     * A cohort is the set of stores in the website that resolve to the same guest list.
     * The mega batch is shared by all cohorts of the website; each contact carries its own list.
     *
     * @param Website $website
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
        Website $website,
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

        $guestExporter = $this->guestExporterFactory->create();
        $guestExporter->setFieldMapping($website);

        do {
            $guests = $this->getGuestsToSync($website->getId(), $cohort, $websiteStoreIds, $limit, $offset);
            if (count($guests->getItems()) === 0) {
                break;
            }

            if ($loopStart) {
                $this->logger->info(
                    sprintf(
                        '%s sync: syncing website %d, list %d',
                        Importer::IMPORT_TYPE_GUEST,
                        $website->getId(),
                        $listId
                    )
                );
                $loopStart = false;
            }

            $batch = $guestExporter->export($guests->getItems(), $website, $listId);
            $batchCount = count($batch);

            if ($batchCount === 0) {
                break;
            }

            $megaBatch = $this->mergeManager->mergeBatch($batch, $megaBatch);

            $offset += count($guests->getItems());
            $megaBatchCount += $batchCount;
            $this->totalGuestsSyncedCount += $batchCount;

            if ($megaBatchCount >= $megaBatchSize) {
                $this->processMegaBatch($megaBatch, (int) $website->getId());
                $megaBatch = [];
                $megaBatchCount = 0;
                $offset = 0;
            }
        } while (!$breakValue || $this->totalGuestsSyncedCount < $breakValue);
    }

    /**
     * Process a mega batch of guest contacts for a website.
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
                Importer::IMPORT_TYPE_GUEST
            );
    }

    /**
     * Get guests to sync for a list cohort.
     *
     * @param string|int $websiteId
     * @param array $cohort
     * @param array $websiteStoreIds
     * @param int $pageSize
     * @param int $offset
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection
     */
    private function getGuestsToSync($websiteId, array $cohort, array $websiteStoreIds, $pageSize, int $offset = 0)
    {
        return $this->contactCollectionFactory->create()
            ->getGuestsByStores(
                $websiteId,
                $cohort['storeIds'],
                $websiteStoreIds,
                $cohort['includeUnassigned'],
                $cohort['onlySubscribers'],
                $pageSize,
                $offset
            );
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
