<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractContactSyncer;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Batch\GuestBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class Guest extends AbstractContactSyncer implements SyncInterface
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
     * @var GuestBatchProcessor
     */
    private $batchProcessor;

    /**
     * @var CsvHandler
     */
    private $csvHandler;

    /**
     * @var ContactData
     */
    private $contactData;

    /**
     * Guest constructor.
     *
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param GuestBatchProcessor $batchProcessor
     * @param ContactData $contactData
     * @param array $data
     */
    public function __construct(
        ContactCollectionFactory $contactCollectionFactory,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Logger $logger,
        GuestBatchProcessor $batchProcessor,
        CsvHandler $csvHandler,
        ContactData $contactData,
        array $data = []
    ) {
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->batchProcessor = $batchProcessor;
        $this->csvHandler = $csvHandler;
        $this->contactData = $contactData;
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
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_LIMIT):
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
                        $breakValue
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
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loopByWebsite(Website $website, int $megaBatchSize, int $breakValue)
    {
        $megaBatch = [];
        $offset = 0;
        $loopStart = true;
        $filename = '';

        $columns = $this->getGuestColumns($website);

        $limit = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );

        do {
            $guests = $this->getGuestsToSync($website->getId(), $limit, $offset);
            if (!$guests->getSize()) {
                break;
            }

            $batch = $this->exportGuests($guests, $columns);
            $batchCount = count($batch);

            if ($batchCount === 0) {
                break;
            }

            if ($loopStart) {
                $filename = $this->csvHandler->initialiseCsvFile($website, $columns, 'Guest');
                $loopStart = false;
            }

            $megaBatch = $this->mergeBatch($batch, $megaBatch);

            $offset += count($guests->getItems());
            $this->totalGuestsSyncedCount += $batchCount;

            if (count($megaBatch) >= $megaBatchSize) {
                $this->batchProcessor->process($megaBatch, $website->getId(), $filename);
                $megaBatch = [];
                $offset = 0;
                $loopStart = true;
            }

        } while (!$breakValue || $this->totalGuestsSyncedCount < $breakValue);

        $this->batchProcessor->process($megaBatch, $website->getId(), $filename);
    }

    /**
     * Export guests.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection $guests
     * @param array $columns
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function exportGuests($guests, $columns)
    {
        $exportedData = [];
        foreach ($guests as $guest) {
            $exportedData[$guest->getEmailContactId()] = $this->contactData
                ->init($guest, $columns)
                ->setContactData()
                ->toCSVArray();
        }

        return $exportedData;
    }

    /**
     * Get guests columns.
     *
     * @param Website $website
     * @return array
     */
    private function getGuestColumns(Website $website)
    {
        $guestColumns = [
            'store_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME),
            'store_name_additional' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME_ADDITIONAL),
            'website_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME)
        ];

        return AbstractExporter::EMAIL_FIELDS + array_filter($guestColumns);
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
}
