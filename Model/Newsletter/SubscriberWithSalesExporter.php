<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

class SubscriberWithSalesExporter
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $file;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    public $subscribersCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\SubscriberFactory
     */
    public $emailSubscriber;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    public $emailContactResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    public $configHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * SubscriberWithSalesExporter constructor.
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\Config $configHelper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\SubscriberFactory $emailSubscriber
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactResource
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Config $configHelper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Dotdigitalgroup\Email\Model\Apiconnector\SubscriberFactory $emailSubscriber,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactResource
    ) {
        $this->importerFactory   = $importerFactory;
        $this->file              = $file;
        $this->helper            = $helper;
        $this->resource          = $resource;
        $this->configHelper      = $configHelper;
        $this->storeManager = $storeManager;
        $this->emailSubscriber = $emailSubscriber;
        $this->emailContactResource = $contactResource;
        $this->subscribersCollection = $subscriberCollection;
    }

    /**
     * @param \Magento\Store\Model\Website $website
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection $subscribers
     *
     * @return int
     */
    public function exportSubscribersWithSales($website, $subscribers)
    {
        $updated = 0;
        $subscriberIds = $headers = $emailContactIdEmail = [];
        foreach ($subscribers as $emailContact) {
            $emailContactIdEmail[$emailContact->getId()] = $emailContact->getEmail();
        }
        $subscribersFile = strtolower($website->getCode() . '_subscribers_with_sales_' . date('d_m_Y_Hi') . '.csv');
        $this->helper->log('Subscriber file with sales : ' . $subscribersFile);
        //get subscriber emails
        $emails = $subscribers->getColumnValues('email');

        //subscriber collection
        $collection = $this->getCollection($emails, $website->getId());
        //no subscribers found
        if ($collection->getSize() == 0) {
            return 0;
        }

        $mappedHash = $this->helper->getWebsiteSalesDataFields($website);
        $headers = $mappedHash;
        $headers[] = 'Email';
        $headers[] = 'EmailType';
        $headers[] = 'OptInType';
        $this->file->outputCSV($this->file->getFilePath($subscribersFile), $headers);
        //subscriber data
        foreach ($collection as $subscriber) {
            $store = $this->storeManager->getStore($subscriber->getStoreId());
            $optInType = $this->configHelper->getOptInType($store);
            $connectorSubscriber = $this->emailSubscriber->create();
            $connectorSubscriber->setMappingHash($mappedHash);
            $connectorSubscriber->setSubscriberData($subscriber);
            //count number of customers
            $index = array_search($subscriber->getSubscriberEmail(), $emailContactIdEmail);
            if ($index) {
                $subscriberIds[] = $index;
            }
            //contact email and email type
            $connectorSubscriber->setData($subscriber->getSubscriberEmail());
            $connectorSubscriber->setData('Html');
            // save csv file data
            $outputData = $connectorSubscriber->toCSVArray();
            $outputData[] = $optInType;
            $this->file->outputCSV($this->file->getFilePath($subscribersFile), $outputData);
            //clear collection and free memory
            $subscriber->clearInstance();
            $updated++;
        }

        $subscriberNum = count($subscriberIds);
        if (is_file($this->file->getFilePath($subscribersFile))) {
            if ($subscriberNum > 0) {
                //register in queue with importer
                $check = $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS,
                        '',
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId(),
                        $subscribersFile
                    );
                //set imported
                if ($check) {
                    $this->emailContactResource->create()
                        ->updateSubscribers($subscriberIds);
                }
            }
        }

        return $updated;
    }

    /**
     * @param array $emails
     * @param int $websiteId
     *
     * @return mixed
     */
    public function getCollection($emails, $websiteId = 0)
    {
        $statuses = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $websiteId
        );
        $statuses = explode(',', $statuses);
        $brand = $this->helper->getBrandAttributeByWebsiteId($websiteId);

        return $this->emailContactResource->create()
            ->getCollectionForSubscribersByEmails($emails, $statuses, $brand);
    }
}
