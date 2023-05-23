<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterProgressHandlerFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterQueueManager;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Magento\Store\Model\StoreManagerInterface;

class Importer implements SyncInterface
{
    public const TOTAL_IMPORT_SYNC_LIMIT = 100;
    public const CONTACT_IMPORT_SYNC_LIMIT = 25;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @var ImporterQueueManager $queueManager
     */
    private $queueManager;

    /**
     * @var ImporterProgressHandlerFactory $progressHandler
     */
    private $progressHandler;

    /**
     * @var AccountHandler
     */
    private $accountHandler;

    /**
     * Importer constructor.
     *
     * @param AccountHandler $accountHandler
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param StoreManagerInterface $storeManager
     * @param ImporterQueueManager $queueManager
     * @param ImporterProgressHandlerFactory $progressHandler
     */
    public function __construct(
        AccountHandler $accountHandler,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        StoreManagerInterface $storeManager,
        ImporterQueueManager $queueManager,
        ImporterProgressHandlerFactory $progressHandler
    ) {
        $this->accountHandler = $accountHandler;
        $this->helper = $helper;
        $this->importerFactory = $importerFactory;
        $this->storeManager = $storeManager;
        $this->queueManager = $queueManager;
        $this->progressHandler = $progressHandler;
    }

    /**
     * Importer sync.
     *
     * @param \DateTime|null $from
     * @return void
     */
    public function sync(\DateTime $from = null)
    {
        $activeApiUsers = $this->accountHandler->getAPIUsersForECEnabledWebsites();
        if (!$activeApiUsers) {
            return;
        }

        $bulkQueue = $this->queueManager->getBulkQueue();
        $singleQueue = $this->queueManager->getSingleQueue();

        foreach ($activeApiUsers as $apiUser) {
            $client = $this->helper->getWebsiteApiClient(
                $apiUser['websites'][0]
            );
            if (!$client) {
                continue;
            }

            $inProgressImports = $this->progressHandler->create(['data' => ['client' => $client]])
                ->checkImportsInProgress($apiUser['websites']);

            $this->processQueue(
                $bulkQueue,
                $apiUser['websites'],
                $client,
                $inProgressImports
            );
            $this->processQueue(
                $singleQueue,
                $apiUser['websites'],
                $client
            );
        }
    }

    /**
     * Process Queue.
     *
     * @param array $queue
     * @param array $websiteIds
     * @param Client $client
     * @param int $itemsCount
     */
    private function processQueue(array $queue, array $websiteIds, Client $client, int $itemsCount = 0)
    {
        foreach ($queue as $sync) {
            if ($itemsCount < self::TOTAL_IMPORT_SYNC_LIMIT) {
                $collection = $this->importerFactory->create()
                    ->_getQueue(
                        $sync['type'],
                        $sync['mode'],
                        $sync['limit'] - $itemsCount,
                        $websiteIds
                    );
                if ($collection->getSize()) {
                    $itemsCount += count($collection);
                    $sync['model']->create(['data' => ['client' => $client]])
                        ->sync($collection);
                }
            }
        }
    }
}
