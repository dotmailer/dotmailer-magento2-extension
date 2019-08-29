<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Helper\Config;
use \Magento\Store\Api\StoreRepositoryInterface;

class Importer implements SyncInterface
{
    //sync limits
    const SYNC_SINGLE_LIMIT_NUMBER = 100;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    private $fileHelper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var StoreRepositoryInterface $storeRepository
     */
    private $storeRepository;

    /**
     * @var array
     */
    private $reasons
        = [
            'Globally Suppressed',
            'Blocked',
            'Unsubscribed',
            'Hard Bounced',
            'Isp Complaints',
            'Domain Suppressed',
            'Failures',
            'Invalid Entries',
            'Mail Blocked',
            'Suppressed by you',
        ];

    /**
     * @var array
     */
    private $importStatuses
        = [
            'RejectedByWatchdog',
            'InvalidFileFormat',
            'Unknown',
            'Failed',
            'ExceedsAllowedContactLimit',
            'NotAvailableInThisVersion',
        ];

    /**
     * @var array
     */
    private $bulkPriority;

    /**
     * @var array
     */
    private $singlePriority;

    /**
     * @var int
     */
    private $totalItems;

    /**
     * @var int
     */
    private $bulkSyncLimit;

    /**
     * Importer constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->helper = $helper;
        $this->fileHelper = $fileHelper;
        $this->importerFactory = $importerFactory;
        $this->objectManager = $objectManager;
        $this->dateTime = $dateTime;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Importer sync
     */
    public function sync(\DateTime $from = null)
    {
        $this->processQueue();
    }

    /**
     * Process the data from queue.
     *
     * @return null
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function processQueue()
    {
        //Set items to 0
        $this->totalItems = 0;

        //Set bulk sync limit
        $this->bulkSyncLimit = 5;

        //Set priority
        $this->_setPriority();

        $enabledWebsites = $this->getECEnabledWebsites();

        //Check previous import status
        $this->_checkImportStatus();

        $importerModel = $this->importerFactory->create();

        //Bulk priority. Process group 1 first
        foreach ($this->bulkPriority as $bulk) {
            if ($this->totalItems < $bulk['limit']) {
                $collection = $importerModel->_getQueue(
                    $bulk['type'],
                    $bulk['mode'],
                    $bulk['limit'] - $this->totalItems,
                    $enabledWebsites
                );
                if ($collection->getSize()) {
                    $this->totalItems += $collection->getSize();
                    $bulkModel = $this->objectManager->create($bulk['model']);
                    $bulkModel->sync($collection);
                }
            }
        }

        //reset total items to 0
        $this->totalItems = 0;

        //Single/Update priority.
        foreach ($this->singlePriority as $single) {
            if ($this->totalItems < $single['limit']) {
                $collection = $importerModel->_getQueue(
                    $single['type'],
                    $single['mode'],
                    $single['limit'] - $this->totalItems,
                    $enabledWebsites
                );
                if ($collection->getSize()) {
                    $this->totalItems += $collection->getSize();
                    $singleModel = $this->objectManager->create(
                        $single['model']
                    );
                    $singleModel->sync($collection);
                }
            }
        }
    }

    /**
     * Set importing priority.
     *
     * @return null
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function _setPriority()
    {
        /*
         * Bulk
         */

        $defaultBulk = [
            'model' => '',
            'mode' => ImporterModel::MODE_BULK,
            'type' => '',
            'limit' => $this->bulkSyncLimit,
        ];

        //Contact Bulk
        $contact = $defaultBulk;
        $contact['model'] = \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk::class;
        $contact['type'] = [
            ImporterModel::IMPORT_TYPE_CONTACT,
            ImporterModel::IMPORT_TYPE_GUEST,
            ImporterModel::IMPORT_TYPE_SUBSCRIBERS,
        ];

        //Bulk Order
        $order = $defaultBulk;
        $order['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Bulk::class;
        $order['type'] = ImporterModel::IMPORT_TYPE_ORDERS;

        //Bulk Other TD
        $other = $defaultBulk;
        $other['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Bulk::class;
        $other['type'] = [
            'Catalog',
            ImporterModel::IMPORT_TYPE_REVIEWS,
            ImporterModel::IMPORT_TYPE_WISHLIST,
        ];

        /*
         * Update
         */
        $defaultSingleUpdate = [
            'model' => \Dotdigitalgroup\Email\Model\Sync\Contact\Update::class,
            'mode' => '',
            'type' => '',
            'limit' => self::SYNC_SINGLE_LIMIT_NUMBER,
        ];

        //Subscriber resubscribe
        $subscriberResubscribe = $defaultSingleUpdate;
        $subscriberResubscribe['mode'] = ImporterModel::MODE_SUBSCRIBER_RESUBSCRIBED;
        $subscriberResubscribe['type'] = ImporterModel::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED;

        //Subscriber update/suppressed
        $subscriberUpdate = $defaultSingleUpdate;
        $subscriberUpdate['mode'] = ImporterModel::MODE_SUBSCRIBER_UPDATE;
        $subscriberUpdate['type'] = ImporterModel::IMPORT_TYPE_SUBSCRIBER_UPDATE;

        //Email Change
        $emailChange = $defaultSingleUpdate;
        $emailChange['mode'] = ImporterModel::MODE_CONTACT_EMAIL_UPDATE;
        $emailChange['type'] = ImporterModel::IMPORT_TYPE_CONTACT_UPDATE;

        //Order Update
        $orderUpdate = $defaultSingleUpdate;
        $orderUpdate['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Update::class;
        $orderUpdate['mode'] = ImporterModel::MODE_SINGLE;
        $orderUpdate['type'] = ImporterModel::IMPORT_TYPE_ORDERS;

        //CartInsight TD update
        $updateCartInsightTd = $defaultSingleUpdate;
        $updateCartInsightTd['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Update::class;
        $updateCartInsightTd['mode'] = ImporterModel::MODE_SINGLE;
        $updateCartInsightTd['type'] = ImporterModel::IMPORT_TYPE_CART_INSIGHT_CART_PHASE;

        //Update Other TD
        $updateOtherTd = $defaultSingleUpdate;
        $updateOtherTd['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Update::class;
        $updateOtherTd['mode'] = ImporterModel::MODE_SINGLE;
        $updateOtherTd['type'] = [
            'Catalog',
            ImporterModel::IMPORT_TYPE_WISHLIST,
        ];

        /*
        * Delete
        */
        $defaultSingleDelete = [
            'model' => '',
            'mode' => '',
            'type' => '',
            'limit' => self::SYNC_SINGLE_LIMIT_NUMBER,
        ];

        //Contact Delete
        $contactDelete = $defaultSingleDelete;
        $contactDelete['model'] = \Dotdigitalgroup\Email\Model\Sync\Contact\Delete::class;
        $contactDelete['mode'] = ImporterModel::MODE_CONTACT_DELETE;
        $contactDelete['type'] = ImporterModel::IMPORT_TYPE_CONTACT;

        //TD Delete
        $tdDelete = $defaultSingleDelete;
        $tdDelete['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Delete::class;
        $tdDelete['mode'] = ImporterModel::MODE_SINGLE_DELETE;
        $tdDelete['type'] = [
            'Catalog',
            ImporterModel::IMPORT_TYPE_REVIEWS,
            ImporterModel::IMPORT_TYPE_WISHLIST,
            ImporterModel::IMPORT_TYPE_ORDERS,
        ];

        //Bulk Priority
        $this->bulkPriority = [
            $contact,
            $order,
            $other,
        ];

        $this->singlePriority = [
            $subscriberResubscribe,
            $subscriberUpdate,
            $emailChange,
            $orderUpdate,
            $updateCartInsightTd,
            $updateOtherTd,
            $contactDelete,
            $tdDelete,
        ];
    }

    /**
     * Check importing status for pending import.
     *
     * @return null
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function _checkImportStatus()
    {
        $importerModel = $this->importerFactory->create();
        if ($items = $importerModel->_getImportingItems($this->bulkSyncLimit)) {
            foreach ($items as $item) {
                $websiteId = $item->getWebsiteId();
                $client = false;
                if ($this->helper->isEnabled($websiteId)) {
                    $client = $this->helper->getWebsiteApiClient(
                        $websiteId
                    );
                }
                if (!$client) {
                    $item->setMessage('No API client found for this website.')
                        ->setImportStatus(ImporterModel::FAILED);
                        $importerModel->saveItem($item);
                        continue;
                }

                try {
                    if ($item->getImportType() == ImporterModel::IMPORT_TYPE_CONTACT ||
                        $item->getImportType() == ImporterModel::IMPORT_TYPE_SUBSCRIBERS ||
                        $item->getImportType() == ImporterModel::IMPORT_TYPE_GUEST
                    ) {
                        $response = $client->getContactsImportByImportId($item->getImportId());
                    } else {
                        $response = $client->getContactsTransactionalDataImportByImportId(
                            $item->getImportId()
                        );
                    }
                } catch (\Exception $e) {
                    $item->setMessage($e->getMessage())
                        ->setImportStatus(ImporterModel::FAILED);
                    $importerModel->saveItem($item);
                    continue;
                }

                $this->processResponse($response, $item, $websiteId);
            }
        }
    }

    /**
     * @param Object $response
     * @param \Dotdigitalgroup\Email\Model\Importer $item
     * @param int $websiteId
     *
     * @return null
     */
    private function processResponse($response, $item, $websiteId)
    {
        if (isset($response->message)) {
            $item->setImportStatus(ImporterModel::FAILED)
                ->setMessage($response->message);
        } else {
            if ($response->status == 'Finished') {
                $item = $this->processFinishedItem($item, $websiteId);
            } elseif (in_array($response->status, $this->importStatuses)) {
                $item->setImportStatus(ImporterModel::FAILED)
                    ->setMessage('Import failed with status ' . $response->status);
            } else {
                //Not finished
                $this->totalItems += 1;
            }
        }
        //Save item
        $this->importerFactory->create()
            ->saveItem($item);
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Importer $item
     * @param int $websiteId
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processFinishedItem($item, $websiteId)
    {
        $now = gmdate('Y-m-d H:i:s');

        $item->setImportStatus(ImporterModel::IMPORTED)
            ->setImportFinished($now)
            ->setMessage('');

        if ($item->getImportType() == ImporterModel::IMPORT_TYPE_CONTACT ||
            $item->getImportType() == ImporterModel::IMPORT_TYPE_SUBSCRIBERS ||
            $item->getImportType() == ImporterModel::IMPORT_TYPE_GUEST
        ) {
            $file = $item->getImportFile();
            // if a filename is stored in the table and if that file physically exists
            if ($file && $this->fileHelper->isFilePathExistWithFallback($file)) {
                //remove the consent data for contacts before archiving the file
                $log = $this->fileHelper->cleanProcessedConsent(
                    $this->fileHelper->getFilePathWithFallback($file)
                );
                if ($log) {
                    $this->helper->log($log);
                }
                if (! $this->fileHelper->isFileAlreadyArchived($file)) {
                    $this->fileHelper->archiveCSV($file);
                }
            }

            if ($item->getImportId()) {
                $this->_processContactImportReportFaults($item->getImportId(), $websiteId);
            }
        }

        return $item;
    }

    /**
     * Get report info for contacts sync.
     *
     * @param int $id
     * @param int $websiteId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return null
     */
    private function _processContactImportReportFaults($id, $websiteId)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $report = $client->getContactImportReportFaults($id);

        if ($report) {
            $reportData = explode(PHP_EOL, $this->_removeUtf8Bom($report));
            //unset header
            unset($reportData[0]);
            //no data in report
            if (! empty($reportData)) {
                $contacts = [];
                foreach ($reportData as $row) {
                    if (empty($row)) {
                        continue;
                    }

                    list ($reason, $email) = explode(',', $row);
                    if (in_array($reason, $this->reasons)) {
                        $contacts[] = $email;
                    }
                }

                // get a time period for the last contact sync
                $cronMinutes = filter_var($this->helper->getConfigValue(Config::XML_PATH_CRON_SCHEDULE_CONTACT), FILTER_SANITIZE_NUMBER_INT);
                $lastSyncPeriod = new \DateTime($this->dateTime->formatDate(true), new \DateTimeZone('UTC'));
                $lastSyncPeriod->sub(new \DateInterval("PT{$cronMinutes}M"));

                // check whether any last subscribe dates fall within the last subscriber sync period
                $recentlyResubscribed = array_filter(
                    $this->helper->contactResource->getLastSubscribedAtDates($contacts),
                    function ($contact) use ($lastSyncPeriod) {
                        $lastSubscribed = new \DateTime($contact['last_subscribed_at'], new \DateTimeZone('UTC'));
                        return $lastSubscribed >= $lastSyncPeriod;
                    }
                );

                if (!empty($recentlyResubscribed)) {
                    $importerModel = $this->importerFactory->create();
                    // queue resubscription jobs
                    foreach ($recentlyResubscribed as $resubscriber) {
                        $importerModel->registerQueue(
                            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
                            ['email' => $resubscriber['email']],
                            \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
                            $websiteId
                        );
                    }

                    // remove from unsubscribable emails
                    $contacts = array_diff($contacts, array_column($recentlyResubscribed, 'email'));
                }

                //unsubscribe from email contact and newsletter subscriber tables
                if (!empty($contacts)) {
                    $this->helper->contactResource->unsubscribe($contacts);
                }
            }
        }
    }

    /**
     * Convert utf8 data.
     *
     * @param string $text
     *
     * @return string
     */
    private function _removeUtf8Bom($text)
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);

        return $text;
    }

    /**
     * Retrieve a list of websites that have Engagement Cloud enabled.
     *
     * @return array
     */
    private function getECEnabledWebsites()
    {
        $stores = $this->storeRepository->getList();
        $websiteIds = [];
        foreach ($stores as $store) {
            $websiteId = $store->getWebsiteId();
            if ($this->helper->isEnabled($websiteId)
                && !in_array($websiteId, $websiteIds)
            ) {
                $websiteIds[] = $websiteId;
            }
        }
        return $websiteIds;
    }
}
