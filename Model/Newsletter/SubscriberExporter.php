<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Dotdigitalgroup\Email\Helper\Config;

class SubscriberExporter
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var CsvGeneratorFactory
     */
    private $csvGeneratorFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    private $subscriberCollectionFactory;

    /**
     * @var array
     */
    private $contactIds;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Consent
     */
    private $consentResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ConsentFactory
     */
    private $consentFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * SubscriberExporter constructor.
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param Config $configHelper
     * @param \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory
     * @param CsvGeneratorFactory $csvGeneratorFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        Config $configHelper,
        \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory,
        CsvGeneratorFactory $csvGeneratorFactory
    ) {
        $this->csvGeneratorFactory    = $csvGeneratorFactory;
        $this->helper          = $helper;
        $this->dateTime        = $dateTime;
        $this->configHelper    = $configHelper;
        $this->consentFactory  = $consentFactory;
        $this->consentResource = $consentResource;
        $this->contactResource = $contactResource;
        $this->importerFactory = $importerFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
    }

    /**
     * Export subscribers
     *
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @param  \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection $emailContactCollection
     *
     * @return int
     */
    public function exportSubscribers($store, $emailContactCollection)
    {
        $updated = 0;
        $websiteId = $store->getWebsiteId();
        $subscribersFilename = strtolower($store->getCode() . '_subscribers_' . date('d_m_Y_His') . '.csv');
        $consentModel = $this->consentFactory->create();
        $subscriberStorename = $store->getWebsite()->getConfig(
            Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME
        );
        $subscriberWebsiteName = $store->getWebsite()->getConfig(
            Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        );

        $csv = $this->csvGeneratorFactory->create()
            ->createCsv($subscribersFilename)
            ->createHeaders(
                $store,
                $subscriberStorename ?: '',
                $subscriberWebsiteName ?: ''
            );

        // If consent insight is enabled, include additional headers
        $isConsentSubscriberEnabled = $this->configHelper->isConsentSubscriberEnabled($websiteId);
        if ($isConsentSubscriberEnabled) {
            $csv->mergeHeaders(\Dotdigitalgroup\Email\Model\Consent::$bulkFields);
            $emailContactCollection->getSelect()
                ->joinLeft(
                    ['ecc' => $emailContactCollection->getTable(Schema::EMAIL_CONTACT_CONSENT_TABLE)],
                    "ecc.email_contact_id = main_table.email_contact_id",
                    ['consent_url', 'consent_datetime', 'consent_ip', 'consent_user_agent']
                )->group('email_contact_id');
        }

        $csv->outputHeadersToFile();
        $optInType = $csv->isOptInTypeDouble($store);

        foreach ($emailContactCollection as $contact) {
            $outputData = [
                $contact->getEmail(),
                'Html',
                $store->getName(),
                $store->getWebsite()->getName()
            ];

            if ($optInType) {
                $outputData[] = 'Double';
            }

            $consentUrl = $contact->getConsentUrl();
            //check for any subscribe or customer consent enabled
            if ($isConsentSubscriberEnabled && $consentUrl) {
                $outputData[] = $consentModel->getConsentTextForWebsite($consentUrl, $websiteId);
                $outputData[] = $consentUrl;
                $outputData[] = $this->dateTime->date(\Zend_Date::ISO_8601, $contact->getConsentDatetime());
                $outputData[] = $contact->getConsentIp();
                $outputData[] = $contact->getConsentUserAgent();
            }
            $this->contactIds[] = $contact->getId();
            $csv->outputDataToFile($outputData);

            $updated++;
        }
        //mark is subscriber imported for contacts
        if (! empty($this->contactIds)) {
            $this->contactResource->updateSubscribers($this->contactIds);
        }
        $this->helper->log('Subscriber filename: ' . $subscribersFilename);
        //register in queue with importer
        $this->importerFactory->create()
            ->registerQueue(
                \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS,
                '',
                \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                $websiteId,
                $subscribersFilename
            );

        return $updated;
    }
}
