<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractContactSyncer;
use Dotdigitalgroup\Email\Model\Sync\Batch\CustomerBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Customer\ExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Customer extends AbstractContactSyncer implements SyncInterface
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
     * @var CustomerBatchProcessor
     */
    private $batchProcessor;

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
     * @var int
     */
    private $totalCustomersSyncedCount = 0;

    /**
     * Customer sync constructor.
     *
     * @param Data $helper
     * @param Logger $logger
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param CustomerBatchProcessor $batchProcessor
     * @param ExporterFactory $exporterFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        ContactCollectionFactory $contactCollectionFactory,
        CustomerBatchProcessor $batchProcessor,
        ExporterFactory $exporterFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->batchProcessor = $batchProcessor;
        $this->exporterFactory = $exporterFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
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
    public function sync(\DateTime $from = null)
    {
        $start = microtime(true);
        $megaBatchSize = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT);
        $breakValue = $this->isRunFromDeveloperButton() ?
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_LIMIT):
            (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE);

        foreach ($this->storeManager->getWebsites() as $website) {
            $apiEnabled = $this->helper->isEnabled($website->getId());
            $customerSyncEnabled = $this->helper->isCustomerSyncEnabled($website->getId());
            $customerAddressBook = $this->helper->getCustomerAddressBook($website->getId());

            if ($apiEnabled &&
                $customerSyncEnabled &&
                $customerAddressBook &&
                (!$breakValue || $this->totalCustomersSyncedCount < $breakValue)
            ) {
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
     * Perform batching loop by website.
     *
     * @param WebsiteInterface $website
     * @param int $megaBatchSize
     * @param int $breakValue
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loopByWebsite(WebsiteInterface $website, int $megaBatchSize, int $breakValue)
    {
        $megaBatch = [];
        $megaBatchCount = 0;
        $offset = 0;
        $loopStart = true;
        $filename = '';
        $limit = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );

        /** @var AbstractExporter $exporter */
        $exporter = $this->exporterFactory->create();
        $exporter->setCsvColumns($website);

        do {
            $customerIds = $this->getCustomerIdsToSync($website->getId(), $limit, $offset);
            $customerIdCount = count($customerIds);
            if ($customerIdCount === 0) {
                break;
            }

            $batch = $exporter->export($customerIds, $website);
            $batchCount = count($batch);
            if ($batchCount === 0) {
                break;
            }

            if ($loopStart) {
                $filename = $exporter->initialiseCsvFile($website, $exporter->getCsvColumns(), 'Customers');
                $loopStart = false;
            }

            $megaBatch = $this->mergeBatch($batch, $megaBatch);
            $megaBatchCount += $batchCount;
            $this->totalCustomersSyncedCount += $batchCount;

            // offset is not always the same as batch count
            $offset += $customerIdCount;

            if ($megaBatchCount >= $megaBatchSize) {
                $this->batchProcessor->process($megaBatch, $website->getId(), $filename);
                $megaBatch = [];
                $megaBatchCount = 0;
                $offset = 0;
                $loopStart = true;
            }
        } while (!$breakValue || $this->totalCustomersSyncedCount < $breakValue);

        $this->batchProcessor->process($megaBatch, $website->getId(), $filename);
    }

    /**
     * Get customer ids to sync.
     *
     * @param int|string $websiteId
     * @param int $pageSize
     * @param int $offset
     *
     * @return array
     */
    private function getCustomerIdsToSync($websiteId, $pageSize, $offset = 0)
    {
        return $this->contactCollectionFactory->create()
            ->getCustomersToImportByWebsite(
                $websiteId,
                $this->helper->isOnlySubscribersForContactSync($websiteId),
                $pageSize,
                $offset
            )->getColumnValues('customer_id');
    }
}
