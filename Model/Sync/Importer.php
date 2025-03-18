<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterProgressHandlerFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterQueueManager;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;

class Importer implements SyncInterface
{
    public const TOTAL_IMPORT_SYNC_LIMIT = 100;
    public const CONTACT_IMPORT_SYNC_LIMIT = 25;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var ClientFactory
     */
    private $v3ClientFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

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
     * @param AccountHandler $accountHandler
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param ClientFactory $v3ClientFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param ImporterQueueManager $queueManager
     * @param ImporterProgressHandlerFactory $progressHandler
     */
    public function __construct(
        AccountHandler $accountHandler,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        ClientFactory $v3ClientFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        ImporterQueueManager $queueManager,
        ImporterProgressHandlerFactory $progressHandler
    ) {
        $this->accountHandler = $accountHandler;
        $this->helper = $helper;
        $this->v3ClientFactory = $v3ClientFactory;
        $this->importerFactory = $importerFactory;
        $this->queueManager = $queueManager;
        $this->progressHandler = $progressHandler;
    }

    /**
     * Importer sync.
     *
     * @param \DateTime|null $from
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(?\DateTime $from = null)
    {
        $activeApiUsers = $this->accountHandler->getAPIUsersForECEnabledWebsites();
        if (!$activeApiUsers) {
            return;
        }

        $bulkQueue = $this->queueManager->getBulkQueue();
        $singleQueue = $this->queueManager->getSingleQueue();

        foreach ($activeApiUsers as $apiUser) {
            $v2Client = $this->helper->getWebsiteApiClient(
                $apiUser['websites'][0]
            );

            $inProgressImports = $this->progressHandler->create()
                ->checkImportsInProgress($apiUser['websites']);

            $this->processQueue(
                $bulkQueue,
                $apiUser['websites'],
                $v2Client,
                $inProgressImports
            );
            $this->processQueue(
                $singleQueue,
                $apiUser['websites'],
                $v2Client
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
