<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterQueueManager;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterProgressHandlerFactory;
use Magento\Store\Model\StoreManagerInterface;

class Importer implements SyncInterface
{
    const TOTAL_IMPORT_SYNC_LIMIT = 100;
    const CONTACT_IMPORT_SYNC_LIMIT = 25;

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
     * Importer constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param StoreManagerInterface $storeManager
     * @param ImporterQueueManager $queueManager
     * @param ImporterProgressHandlerFactory $progressHandlerFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        StoreManagerInterface $storeManager,
        ImporterQueueManager $queueManager,
        ImporterProgressHandlerFactory $progressHandler
    ) {
        $this->helper = $helper;
        $this->importerFactory = $importerFactory;
        $this->storeManager = $storeManager;
        $this->queueManager = $queueManager;
        $this->progressHandler = $progressHandler;
    }

    /**
     * Importer sync
     *
     * @return null
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function sync(\DateTime $from = null)
    {
        $activeApiUsers = $this->getAPIUsersForECEnabledWebsites();
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
     * @param array $queue
     * @param array $websiteIds
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     * @param int $itemsCount
     */
    private function processQueue($queue, $websiteIds, $client, $itemsCount = 0)
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

    /**
     * Retrieve a list of active API users with the websites they are associated with.
     *
     * @return array
     */
    private function getAPIUsersForECEnabledWebsites()
    {
        $websites = $this->storeManager->getWebsites(true);
        $apiUsers = [];
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            if ($this->helper->isEnabled($websiteId)) {
                $apiUser = $this->helper->getApiUsername($websiteId);
                $apiUsers[$apiUser]['websites'][] = $websiteId;
            }
        }
        return $apiUsers;
    }
}
