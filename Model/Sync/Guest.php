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
        parent::__construct($data);
    }

    /**
     * Guest sync.
     *
     * @param \DateTime|null $from
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(\DateTime $from = null)
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
            $addressbook = $this->helper->getGuestAddressBook($website->getId());

            if ($apiEnabled &&
                $guestSyncEnabled &&
                $addressbook &&
                (!$breakValue || $this->totalGuestsSyncedCount < $breakValue)) {
                try {
                    $this->loopByWebsite(
                        $website,
                        $megaBatchSize,
                        $breakValue,
                        (int) $addressbook
                    );
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
     * Loop by website.
     *
     * @param Website $website
     * @param int $megaBatchSize
     * @param int $breakValue
     * @param int $listId
     *
     * @return void
     * @throws Exception
     * @throws LocalizedException
     */
    private function loopByWebsite(Website $website, int $megaBatchSize, int $breakValue, int $listId)
    {
        $megaBatch = [];
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
            $guests = $this->getGuestsToSync($website->getId(), $limit, $offset);
            if (count($guests->getItems()) === 0) {
                break;
            }

            if ($loopStart) {
                $this->logger->info(
                    sprintf(
                        '----------- %s sync ----------- : Website %d',
                        Importer::IMPORT_TYPE_GUEST,
                        $website->getId()
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
            $this->totalGuestsSyncedCount += $batchCount;

            if (count($megaBatch) >= $megaBatchSize) {
                $this->megaBatchProcessorFactory->create()
                    ->process(
                        $megaBatch,
                        (int) $website->getId(),
                        Importer::IMPORT_TYPE_GUEST
                    );
                $megaBatch = [];
                $offset = 0;
            }
        } while (!$breakValue || $this->totalGuestsSyncedCount < $breakValue);

        $this->megaBatchProcessorFactory->create()
            ->process(
                $megaBatch,
                (int) $website->getId(),
                Importer::IMPORT_TYPE_GUEST
            );
    }

    /**
     * Get guests to sync.
     *
     * @param string|int $websiteId
     * @param int $pageSize
     * @param int $offset
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection
     */
    private function getGuestsToSync($websiteId, $pageSize, int $offset = 0)
    {
        return $this->contactCollectionFactory->create()
            ->getGuests(
                $websiteId,
                $this->helper->isOnlySubscribersForContactSync($websiteId),
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
