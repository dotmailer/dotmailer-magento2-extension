<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigitalgroup\Email\Setup\Schema;

class SubscriberExporter
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    public $configHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $dateTime;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    public $subscriberCollectionFactory;

    /**
     *
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
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\Config $configHelper
     * @param \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Config $configHelper,
        \Dotdigitalgroup\Email\Model\ConsentFactory $consentFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->file            = $file;
        $this->helper          = $helper;
        $this->dateTime        = $dateTime;
        $this->configHelper    = $configHelper;
        $this->storeManager    = $storeManager;
        $this->consentFactory  = $consentFactory;
        $this->consentResource = $consentResource;
        $this->contactResource = $contactResource;
        $this->importerFactory = $importerFactory;
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
    }

    /**
     * Export subscribers
     *
     * @param \Magento\Store\Model\Website $website
     * @param  \Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection $emailContactCollection
     *
     * @return int
     */
    public function exportSubscribers($website, $emailContactCollection)
    {
        $updated = 0;
        $websiteId = $website->getId();
        $subscribersFilename = strtolower($website->getCode() . '_subscribers_' . date('d_m_Y_His') . '.csv');
        $consentModel = $this->consentFactory->create();
        //get mapped storename
        $subscriberStorename = $this->helper->getMappedStoreName($website);
        //file headers
        $headers = ['Email', 'EmailType', $subscriberStorename, 'OptInType'];
        //contentinsight is enabled include additional headers
        $isConsentSubscriberEnabled = $this->configHelper->isConsentSubscriberEnabled($websiteId);
        if ($isConsentSubscriberEnabled) {
            $headers = array_merge($headers, \Dotdigitalgroup\Email\Model\Consent::$bulkFields);
            $emailContactCollection->getSelect()
                ->joinLeft(
                    ['ecc' => $emailContactCollection->getTable(Schema::EMAIL_CONTACT_CONSENT_TABLE)],
                    "ecc.email_contact_id = main_table.email_contact_id",
                    ['consent_url', 'consent_datetime', 'consent_ip', 'consent_user_agent']
                );
        }
        $subscribersData = $this->subscriberCollectionFactory->create()
            ->addFieldToFilter(
                'subscriber_email',
                ['in' => $emailContactCollection->getColumnValues('email')]
            )
            ->addFieldToSelect(['subscriber_email', 'store_id']);
        //csv file data with headers
        $this->file->outputCSV($this->file->getFilePath($subscribersFilename), $headers);

        foreach ($emailContactCollection as $contact) {
            $email = $contact->getEmail();
            $storeId = $this->getStoreIdForSubscriber(
                $email,
                $subscribersData->getItems()
            );
            $store = $this->storeManager->getStore($storeId);
            $storeName = $store->getName();
            $optInType = $this->configHelper->getOptInType($store);
            // save data for subscribers
            $outputData = [$email, 'Html', $storeName, $optInType];
            $consentUrl = $contact->getConsentUrl();
            //check for any subscribe or customer consent enabled
            if ($isConsentSubscriberEnabled && $consentUrl) {
                $consentText = $consentModel->getConsentTextForWebsite($consentUrl, $websiteId);
                $consentData = [
                    $consentText,
                    $consentUrl,
                    $this->dateTime->date(\Zend_Date::ISO_8601, $contact->getConsentDatetime()),
                    $contact->getConsentIp(),
                    $contact->getConsentUserAgent()
                ];
                $outputData = array_merge($outputData, $consentData);
            }
            $this->contactIds[] = $contact->getId();
            //csv subscriber data
            $this->file->outputCSV($this->file->getFilePath($subscribersFilename), $outputData);

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

    /**
     * Get the store id from newsletter_subscriber, return default if not found.
     *
     * @param string $email
     * @param array $subscribers
     *
     * @return int
     */
    public function getStoreIdForSubscriber($email, $subscribers)
    {
        $defaultStore = 1;
        foreach ($subscribers as $subscriber) {
            if ($subscriber['subscriber_email'] == $email) {
                return $subscriber['store_id'];
            }
        }
        return $defaultStore;
    }
}
