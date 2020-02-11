<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Dotdigitalgroup\Email\Model\Newsletter\CsvGeneratorFactory;
use Magento\Framework\Filesystem\DriverInterface;

class SubscriberWithSalesExporter
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;

    /**
     * @var CsvGeneratorFactory
     */
    private $csvGeneratorFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $emailContactResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    private $configHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Consent
     */
    private $consentResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ConsentFactory
     */
    private $consentFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\ContactDataFactory
     */
    private $contactDataFactory;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * SubscriberWithSalesExporter constructor.
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\ContactDataFactory $contactDataFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param CsvGeneratorFactory $csvGeneratorFactory
     * @param DriverInterface $driver
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Dotdigitalgroup\Email\Model\Apiconnector\ContactDataFactory $contactDataFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        CsvGeneratorFactory $csvGeneratorFactory,
        DriverInterface $driver
    ) {
        $this->dateTime = $dateTime;
        $this->helper           = $helper;
        $this->resource         = $resource;
        $this->importerFactory  = $importerFactory;
        $this->csvGeneratorFactory = $csvGeneratorFactory;
        $this->configHelper     = $this->helper->configHelperFactory->create();
        $this->consentFactory   = $consentFactory;
        $this->consentResource  = $consentResource;
        $this->contactDataFactory = $contactDataFactory;
        $this->emailContactResource = $contactResource;
        $this->driver = $driver;
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection $contactSubscribers
     *
     * @return int
     */
    public function exportSubscribersWithSales($store, $contactSubscribers)
    {
        $updated = 0;
        $website = $store->getWebsite();
        $websiteId = $website->getId();
        $consentModel = $this->consentFactory->create();
        $mappedHash = $this->helper->getWebsiteSalesDataFields($website);
        $isConsentSubscriberEnabled = $this->configHelper->isConsentSubscriberEnabled($websiteId);
        $emails = $contactSubscribers->getColumnValues('email');
        $emailContactIds = $contactSubscribers->getColumnValues('email_contact_id');
        $subscribersFile = strtolower($store->getCode() . '_subscribers_with_sales_' . date('d_m_Y_His') . '.csv');
        $this->helper->log('Subscriber file with sales : ' . $subscribersFile);
        $contactSubscriberCollection = $this->emailContactResource->getContactCollectionByEmail($emails);

        //no subscribers found
        if ($contactSubscriberCollection->getSize() == 0) {
            return $updated;
        }

        $csv = $this->csvGeneratorFactory->create()
            ->createCsv($subscribersFile)
            ->createHeaders($store)
            ->mergeHeaders($mappedHash);

        //consent data append
        if ($isConsentSubscriberEnabled) {
            $csv->mergeHeaders(\Dotdigitalgroup\Email\Model\Consent::$bulkFields);
            $contactSubscriberCollection->getSelect()
                ->joinLeft(
                    ['ecc' => $contactSubscriberCollection->getTable(Schema::EMAIL_CONTACT_CONSENT_TABLE)],
                    "ecc.email_contact_id = main_table.email_contact_id",
                    ['consent_url', 'consent_datetime', 'consent_ip', 'consent_user_agent']
                )->group('email_contact_id');
        }

        //subscribers sales data
        $salesDataForSubscribers = $this->emailContactResource->getSalesDataForSubscribersWithOrderStatusesAndBrand(
            $emails,
            $websiteId
        );

        $csv->outputHeadersToFile();
        $optInType = $csv->isOptInTypeDouble($store);

        foreach ($contactSubscriberCollection as $subscriber) {
            if (isset($salesDataForSubscribers[$subscriber->getEmail()])) {
                $subscriber = $this->setSalesDataOnItem(
                    $salesDataForSubscribers[$subscriber->getEmail()],
                    $subscriber
                );
            }

            $connectorSubscriber = $this->contactDataFactory->create()
                ->init($subscriber, $mappedHash)
                ->setContactData();

            $outputData = [$subscriber->getEmail(), 'Html'];
            if ($optInType) {
                $outputData[] = 'Double';
            }
            foreach ($connectorSubscriber->toCSVArray() as $item) {
                $outputData[] = $item;
            }

            $consentUrl = $subscriber->getConsentUrl();
            //check for any subscribe or customer consent enabled
            if ($isConsentSubscriberEnabled && $consentUrl) {
                $outputData[] = $consentModel->getConsentTextForWebsite($consentUrl, $websiteId);
                $outputData[] = $consentUrl;
                $outputData[] = $this->dateTime->date(\Zend_Date::ISO_8601, $subscriber->getConsentDatetime());
                $outputData[] = $subscriber->getConsentIp();
                $outputData[] = $subscriber->getConsentUserAgent();
            }

            $csv->outputDataToFile($outputData);
            //clear contactSubscriberCollection and free memory
            $subscriber->clearInstance();
            $updated++;
        }

        $this->registerWithImporter($emailContactIds, $subscribersFile, $websiteId);

        return $updated;
    }

    /**
     * @param array $salesData
     * @param \Dotdigitalgroup\Email\Model\Contact $item
     *
     * @return \Dotdigitalgroup\Email\Model\Contact
     */
    private function setSalesDataOnItem($salesData, $item)
    {
        foreach ($salesData as $column => $value) {
            $item->setData($column, $value);
        }
        return $item;
    }

    /**
     * Register data with importer
     *
     * @param array $emailContactIds
     * @param string $subscribersFile
     * @param $websiteId
     */
    private function registerWithImporter($emailContactIds, $subscribersFile, $websiteId)
    {
        $subscriberNum = count($emailContactIds);
        if ($this->driver->isFile($this->csvGeneratorFactory->create()->getFilePath($subscribersFile))) {
            if ($subscriberNum > 0) {
                //register in queue with importer
                $check = $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS,
                        '',
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $websiteId,
                        $subscribersFile
                    );
                //mark contacts as imported
                if ($check) {
                    $this->emailContactResource->updateSubscribers($emailContactIds);
                }
            }
        }
    }
}
