<?php

namespace Dotdigitalgroup\Email\Model;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Importer extends \Magento\Framework\Model\AbstractModel
{
    const NOT_IMPORTED = 0;
    const IMPORTING = 1;
    const IMPORTED = 2;
    const FAILED = 3;

    //import mode
    const MODE_BULK = 'Bulk';
    const MODE_SINGLE = 'Single';
    const MODE_SINGLE_DELETE = 'Single_Delete';
    const MODE_CONTACT_DELETE = 'Contact_Delete';
    const MODE_SUBSCRIBER_UPDATE = 'Subscriber_Update';
    const MODE_CONTACT_EMAIL_UPDATE = 'Contact_Email_Update';
    const MODE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber_Resubscribed';

    //import type
    const IMPORT_TYPE_GUEST = 'Guest';
    const IMPORT_TYPE_ORDERS = 'Orders';
    const IMPORT_TYPE_CONTACT = 'Contact';
    const IMPORT_TYPE_REVIEWS = 'Reviews';
    const IMPORT_TYPE_WISHLIST = 'Wishlist';
    const IMPORT_TYPE_CONTACT_UPDATE = 'Contact';
    const IMPORT_TYPE_SUBSCRIBERS = 'Subscriber';
    const IMPORT_TYPE_SUBSCRIBER_UPDATE = 'Subscriber';
    const IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED = 'Subscriber';

    //sync limits
    const SYNC_SINGLE_LIMIT_NUMBER = 100;

    /**
     * @var ResourceModel\Importer
     */
    private $importerResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var array
     */
    public $reasons
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
    public $importStatuses
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
    public $bulkPriority;

    /**
     * @var array
     */
    public $singlePriority;

    /**
     * @var int
     */
    public $totalItems;
    
    /**
     * @var int
     */
    public $bulkSyncLimit;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    public $dateTime;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    public $file;

    /**
     * @var ResourceModel\Contact
     */
    public $contact;
    
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    public $directoryList;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $fileHelper;

    /**
     * @var Config\Json
     */
    public $serializer;

    /**
     * Importer constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param ResourceModel\Contact $contact
     * @param ResourceModel\Importer $importerResource
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param Config\Json $serializer
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param array $data
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contact,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Dotdigitalgroup\Email\Model\Config\Json $serializer,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        array $data = [],
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {
        $this->file          = $file;
        $this->helper        = $helper;
        $this->importerResource = $importerResource;
        $this->directoryList = $directoryList;
        $this->objectManager = $objectManager;
        $this->contact       = $contact;
        $this->dateTime      = $dateTime;
        $this->fileHelper    = $fileHelper;
        $this->serializer    = $serializer;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Constructor.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Importer::class);
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($this->dateTime->formatDate(true));
        }
        $this->setUpdatedAt($this->dateTime->formatDate(true));

        return $this;
    }

    /**
     * Register import in queue.
     *
     * @param string $importType
     * @param mixed $importData
     * @param string $importMode
     * @param int $websiteId
     * @param bool $file
     *
     * @return bool
     */
    public function registerQueue(
        $importType,
        $importData,
        $importMode,
        $websiteId,
        $file = false
    ) {
        try {
            if (! empty($importData)) {
                $importData = $this->serializer->serialize($importData);
            }

            if ($file) {
                $this->setImportFile($file);
            }

            if ($importData || $file) {
                $this->setImportType($importType)
                    ->setImportData($importData)
                    ->setWebsiteId($websiteId)
                    ->setImportMode($importMode);

                $this->importerResource->save($this);

                return true;
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        if ($this->serializer->jsonError) {
            $jle = $this->serializer->jsonError;
            $format = "Json error ($jle) for Import type ($importType) / mode ($importMode) for website ($websiteId)";
            $this->helper->log($format);
        }

        return false;
    }

    /**
     * Proccess the data from queue.
     *
     * @return null
     */
    public function processQueue()
    {
        //Set items to 0
        $this->totalItems = 0;

        //Set bulk sync limit
        $this->bulkSyncLimit = 5;

        //Set priority
        $this->_setPriority();

        //Check previous import status
        $this->_checkImportStatus();

        //Bulk priority. Process group 1 first
        foreach ($this->bulkPriority as $bulk) {
            if ($this->totalItems < $bulk['limit']) {
                $collection = $this->_getQueue(
                    $bulk['type'],
                    $bulk['mode'],
                    $bulk['limit'] - $this->totalItems
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
                $collection = $this->_getQueue(
                    $single['type'],
                    $single['mode'],
                    $single['limit'] - $this->totalItems
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
    public function _setPriority()
    {
        /*
         * Bulk
         */

        $defaultBulk = [
            'model' => '',
            'mode' => self::MODE_BULK,
            'type' => '',
            'limit' => $this->bulkSyncLimit,
        ];

        //Contact Bulk
        $contact = $defaultBulk;
        $contact['model'] = \Dotdigitalgroup\Email\Model\Sync\Contact\Bulk::class;
        $contact['type'] = [
            self::IMPORT_TYPE_CONTACT,
            self::IMPORT_TYPE_GUEST,
            self::IMPORT_TYPE_SUBSCRIBERS,
        ];

        //Bulk Order
        $order = $defaultBulk;
        $order['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Bulk::class;
        $order['type'] = self::IMPORT_TYPE_ORDERS;

        //Bulk Other TD
        $other = $defaultBulk;
        $other['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Bulk::class;
        $other['type'] = [
            'Catalog',
            self::IMPORT_TYPE_REVIEWS,
            self::IMPORT_TYPE_WISHLIST,
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
        $subscriberResubscribe['mode'] = self::MODE_SUBSCRIBER_RESUBSCRIBED;
        $subscriberResubscribe['type'] = self::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED;

        //Subscriber update/suppressed
        $subscriberUpdate = $defaultSingleUpdate;
        $subscriberUpdate['mode'] = self::MODE_SUBSCRIBER_UPDATE;
        $subscriberUpdate['type'] = self::IMPORT_TYPE_SUBSCRIBER_UPDATE;

        //Email Change
        $emailChange = $defaultSingleUpdate;
        $emailChange['mode'] = self::MODE_CONTACT_EMAIL_UPDATE;
        $emailChange['type'] = self::IMPORT_TYPE_CONTACT_UPDATE;

        //Order Update
        $orderUpdate = $defaultSingleUpdate;
        $orderUpdate['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Update::class;
        $orderUpdate['mode'] = self::MODE_SINGLE;
        $orderUpdate['type'] = self::IMPORT_TYPE_ORDERS;

        //Update Other TD
        $updateOtherTd = $defaultSingleUpdate;
        $updateOtherTd['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Update::class;
        $updateOtherTd['mode'] = self::MODE_SINGLE;
        $updateOtherTd['type'] = [
            'Catalog',
            self::IMPORT_TYPE_WISHLIST,
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
        $contactDelete['mode'] = self::MODE_CONTACT_DELETE;
        $contactDelete['type'] = self::IMPORT_TYPE_CONTACT;

        //TD Delete
        $tdDelete = $defaultSingleDelete;
        $tdDelete['model'] = \Dotdigitalgroup\Email\Model\Sync\Td\Delete::class;
        $tdDelete['mode'] = self::MODE_SINGLE_DELETE;
        $tdDelete['type'] = [
            'Catalog',
            self::IMPORT_TYPE_REVIEWS,
            self::IMPORT_TYPE_WISHLIST,
            self::IMPORT_TYPE_ORDERS,
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
            $updateOtherTd,
            $contactDelete,
            $tdDelete,
        ];
    }

    /**
     * Check importing status for pending import.
     *
     * @return null
     */
    public function _checkImportStatus()
    {
        if ($items = $this->_getImportingItems($this->bulkSyncLimit)) {
            foreach ($items as $item) {
                $websiteId = $item->getWebsiteId();
                $client = false;
                if ($this->helper->isEnabled($websiteId)) {
                    $client = $this->helper->getWebsiteApiClient(
                        $websiteId
                    );
                }
                if ($client) {
                    try {
                        if ($item->getImportType() == self::IMPORT_TYPE_CONTACT
                            or
                            $item->getImportType()
                            == self::IMPORT_TYPE_SUBSCRIBERS
                            or
                            $item->getImportType() == self::IMPORT_TYPE_GUEST
                        ) {
                            $response = $client->getContactsImportByImportId(
                                $item->getImportId()
                            );
                        } else {
                            $response
                                = $client->getContactsTransactionalDataImportByImportId(
                                    $item->getImportId()
                                );
                        }
                    } catch (\Exception $e) {
                        $item->setMessage($e->getMessage())
                            ->setImportStatus(self::FAILED);
                        $this->saveItem($item);
                        continue;
                    }

                    $this->processResponse($response, $item, $websiteId);
                }
            }
        }
    }

    /**
     * @param mixed $response
     * @param mixed $item
     * @param int $websiteId
     *
     * @return null
     */
    private function processResponse($response, $item, $websiteId)
    {
        if (isset($response->message)) {
            $item->setImportStatus(self::FAILED)
                ->setMessage($response->message);
        } else {
            if ($response->status == 'Finished') {
                $now = gmdate('Y-m-d H:i:s');

                $item->setImportStatus(self::IMPORTED)
                    ->setImportFinished($now)
                    ->setMessage('');

                if ($item->getImportType()
                    == self::IMPORT_TYPE_CONTACT or
                    $item->getImportType()
                    == self::IMPORT_TYPE_SUBSCRIBERS or
                    $item->getImportType()
                    == self::IMPORT_TYPE_GUEST
                ) {
                    //if file
                    if ($file = $item->getImportFile()) {
                        $this->fileHelper->archiveCSV($file);
                    }

                    if ($item->getImportId()) {
                        $this->_processContactImportReportFaults(
                            $item->getImportId(),
                            $websiteId
                        );
                    }
                }
            } elseif (in_array(
                $response->status,
                $this->importStatuses
            )) {
                $item->setImportStatus(self::FAILED)
                    ->setMessage(
                        'Import failed with status '
                        . $response->status
                    );
            } else {
                //Not finished
                $this->totalItems += 1;
            }
        }
        //Save item
        $this->saveItem($item);
    }

    /**
     * @param mixed $itemToSave
     *
     * @return null
     */
    private function saveItem($itemToSave)
    {
        $this->importerResource->save($itemToSave);
    }

    /**
     * Get imports marked as importing.
     *
     * @param int $limit
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection|bool
     */
    public function _getImportingItems($limit)
    {
        return $this->getCollection()
            ->getItemsWithImportingStatus($limit);
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
    public function _processContactImportReportFaults($id, $websiteId)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $response = $client->getContactImportReportFaults($id);

        if ($response) {
            $data = $this->_removeUtf8Bom($response);
            $fileName = $this->directoryList->getPath('var')
                . DIRECTORY_SEPARATOR . 'DmTempCsvFromApi.csv';
            $this->file->open();
            $check = $this->file->write($fileName, $data);

            if ($check) {
                $csvArray = $this->_csvToArray($fileName);
                $this->file->rm($fileName);
                $this->contact->unsubscribe($csvArray);
            } else {
                $this->helper->log(
                    '_processContactImportReportFaults: cannot save data to CSV file.'
                );
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
    public function _removeUtf8Bom($text)
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);

        return $text;
    }

    /**
     * Convert csv data to array.
     *
     * @param string $filename
     *
     * @return array|bool
     */
    public function _csvToArray($filename)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = null;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        $contacts = [];
        foreach ($data as $item) {
            if (in_array($item['Reason'], $this->reasons)) {
                $contacts[] = $item['email'];
            }
        }

        return $contacts;
    }

    /**
     * Get the imports by type.
     *
     * @param string $importType
     * @param string $importMode
     * @param int $limit
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function _getQueue($importType, $importMode, $limit)
    {
        return $this->getCollection()
            ->getQueueByTypeAndMode($importType, $importMode, $limit);
    }
}
