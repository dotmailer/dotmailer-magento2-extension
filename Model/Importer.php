<?php

namespace Dotdigitalgroup\Email\Model;

/**
 * Class Importer
 * @package Dotdigitalgroup\Email\Model
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
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
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
     * @var
     */
    public $bulkPriority;
    /**
     * @var
     */
    public $singlePriority;
    /**
     * @var
     */
    public $totalItems;
    /**
     * @var
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
     * Importer constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                           $helper
     * @param ResourceModel\Contact                                        $contact
     * @param \Dotdigitalgroup\Email\Helper\File                           $fileHelper
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\App\Filesystem\DirectoryList              $directoryList
     * @param \Magento\Framework\ObjectManagerInterface                    $objectManager
     * @param \Magento\Framework\Filesystem\Io\File                        $file
     * @param \Magento\Framework\Stdlib\DateTime                           $dateTime
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contact,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->file          = $file;
        $this->helper        = $helper;
        $this->directoryList = $directoryList;
        $this->objectManager = $objectManager;
        $this->contact       = $contact;
        $this->dateTime      = $dateTime;
        $this->fileHelper    = $fileHelper;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Constructor.
     */
    public function _construct()  //@codingStandardsIgnoreLine
    {
        $this->_init('Dotdigitalgroup\Email\Model\ResourceModel\Importer');
    }

    /**
     * @return $this
     * @codingStandardsIgnoreStart
     */
    public function beforeSave()
    {
        parent::beforeSave();
        //@codingStandardsIgnoreEnd
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
     * @param        $importData
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
            if (!empty($importData)) {
                $importData = serialize($importData);
            }

            if ($file) {
                $this->setImportFile($file);
            }

            $this->setImportType($importType)
                ->setImportData($importData)
                ->setWebsiteId($websiteId)
                ->setImportMode($importMode)
                ->save();

            return true;
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return false;
    }

    /**
     * Proccess the data from queue.
     */
    public function processQueue()
    {
        //Set items to 0
        $this->totalItems = 0;

        //Set bulk sync limit
        $this->bulkSyncLimit = 5;

        //Set priority
        $this->setPriority();

        //Check previous import status
        $this->checkImportStatus();

        //Bulk priority. Process group 1 first
        foreach ($this->bulkPriority as $bulk) {
            if ($this->totalItems < $bulk['limit']) {
                $collection = $this->getQueue(
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
                $collection = $this->getQueue(
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
     */
    public function setPriority()
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
        $contact['model'] = 'Dotdigitalgroup\Email\Model\Sync\Contact\Bulk';
        $contact['type'] = [
            self::IMPORT_TYPE_CONTACT,
            self::IMPORT_TYPE_GUEST,
            self::IMPORT_TYPE_SUBSCRIBERS,
        ];

        //Bulk Order
        $order = $defaultBulk;
        $order['model'] = 'Dotdigitalgroup\Email\Model\Sync\Td\Bulk';
        $order['type'] = self::IMPORT_TYPE_ORDERS;

        //Bulk Other TD
        $other = $defaultBulk;
        $other['model'] = 'Dotdigitalgroup\Email\Model\Sync\Td\Bulk';
        $other['type'] = [
            'Catalog',
            self::IMPORT_TYPE_REVIEWS,
            self::IMPORT_TYPE_WISHLIST,
        ];

        /*
         * Update
         */
        $defaultSingleUpdate = [
            'model' => 'Dotdigitalgroup\Email\Model\Sync\Contact\Update',
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
        $orderUpdate['model'] = 'Dotdigitalgroup\Email\Model\Sync\Td\Update';
        $orderUpdate['mode'] = self::MODE_SINGLE;
        $orderUpdate['type'] = self::IMPORT_TYPE_ORDERS;

        //Update Other TD
        $updateOtherTd = $defaultSingleUpdate;
        $updateOtherTd['model'] = 'Dotdigitalgroup\Email\Model\Sync\Td\Update';
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
        $contactDelete['model'] = 'Dotdigitalgroup\Email\Model\Sync\Contact\Delete';
        $contactDelete['mode'] = self::MODE_CONTACT_DELETE;
        $contactDelete['type'] = self::IMPORT_TYPE_CONTACT;

        //TD Delete
        $tdDelete = $defaultSingleDelete;
        $tdDelete['model'] = 'Dotdigitalgroup\Email\Model\Sync\Td\Delete';
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
     */
    public function checkImportStatus()
    {
        if ($items = $this->getImportingItems($this->bulkSyncLimit)) {
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
                        //@codingStandardsIgnoreStart
                        $item->setMessage($e->getMessage())
                            ->setImportStatus(self::FAILED)
                            ->save();
                        continue;
                    }

                    if ($response) {
                        if ($response->status == 'Finished') {
                            $now = gmdate('Y-m-d H:i:s');

                            $item->setImportStatus(self::IMPORTED)
                                ->setImportFinished($now)
                                ->setMessage('')
                                ->save();
                            if (
                                $item->getImportType()
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
                                    $this->processContactImportReportFaults(
                                        $item->getImportId(), $websiteId
                                    );
                                }
                            }
                        } elseif (in_array(
                            $response->status, $this->importStatuses
                        )) {
                            $item->setImportStatus(self::FAILED)
                                ->setMessage(
                                    'Import failed with status '
                                    . $response->status
                                )
                                ->save();
                            //@codingStandardsIgnoreEnd
                        } else {
                            //Not finished
                            $this->totalItems += 1;
                        }
                    }
                }
            }
        }
    }

    /**
     * Get imports marked as importing.
     *
     * @param $limit
     *
     * @return $this|bool
     */
    public function getImportingItems($limit)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('import_status', ['eq' => self::IMPORTING])
            ->addFieldToFilter('import_id', ['neq' => ''])
            ->setPageSize($limit)
            ->setCurPage(1);

        if ($collection->getSize()) {
            return $collection;
        }

        return false;
    }

    /**
     * Get report info for contacts sync.
     *
     * @param int $id
     * @param int $websiteId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processContactImportReportFaults($id, $websiteId)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $response = $client->getContactImportReportFaults($id);

        if ($response) {
            $data = $this->removeUtf8Bom($response);
            $fileName = $this->directoryList->getPath('var')
                . DIRECTORY_SEPARATOR . 'DmTempCsvFromApi.csv';
            $this->file->open();
            $check = $this->file->write($fileName, $data);

            if ($check) {
                $csvArray = $this->csvToArray($fileName);
                $this->file->rm($fileName);
                $this->contact->unsubscribe($csvArray);
            } else {
                $this->helper->log(
                    'processContactImportReportFaults: cannot save data to CSV file.'
                );
            }
        }
    }

    /**
     * Convert utf8 data.
     *
     * @param $text
     *
     * @return mixed
     */
    public function removeUtf8Bom($text)
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
    public function csvToArray($filename)
    {
        //@codingStandardsIgnoreStart
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
            //@codingStandardsIgnoreEnd
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
    public function getQueue($importType, $importMode, $limit)
    {
        $collection = $this->getCollection();

        if (is_array($importType)) {
            $condition = [];
            foreach ($importType as $type) {
                if ($type == 'Catalog') {
                    $condition[] = ['like' => $type . '%'];
                } else {
                    $condition[] = ['eq' => $type];
                }
            }
            $collection->addFieldToFilter('import_type', $condition);
        } else {
            $collection->addFieldToFilter(
                'import_type',
                ['eq' => $importType]
            );
        }

        $collection->addFieldToFilter('import_mode', ['eq' => $importMode])
            ->addFieldToFilter(
                'import_status',
                ['eq' => self::NOT_IMPORTED]
            )
            ->setPageSize($limit)
            ->setCurPage(1);

        return $collection;
    }
}
