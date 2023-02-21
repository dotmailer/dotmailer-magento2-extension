<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Consent;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Customer\Exporter as CustomerExporter;
use Dotdigitalgroup\Email\Model\Sync\Customer\ExporterFactory as CustomerExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Guest\GuestExporter;
use Dotdigitalgroup\Email\Model\Sync\Guest\GuestExporterFactory as GuestExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\OrderHistoryChecker;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class DataFieldCollector
{
    /**
     * @var CustomerExporterFactory
     */
    private $customerExporterFactory;

    /**
     * @var GuestExporterFactory
     */
    private $guestExporterFactory;

    /**
     * @var OrderHistoryChecker
     */
    private $orderHistoryChecker;

    /**
     * @var SubscriberExporterFactory
     */
    private $subscriberExporterFactory;

    /**
     * @var SubscriberWithSalesExporterFactory
     */
    private $subscriberWithSalesExporterFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CustomerExporterFactory $customerExporterFactory
     * @param GuestExporterFactory $guestExporterFactory
     * @param OrderHistoryChecker $orderHistoryChecker
     * @param SubscriberExporterFactory $subscriberExporterFactory
     * @param SubscriberWithSalesExporterFactory $subscriberWithSalesExporterFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CustomerExporterFactory $customerExporterFactory,
        GuestExporterFactory $guestExporterFactory,
        OrderHistoryChecker $orderHistoryChecker,
        SubscriberExporterFactory $subscriberExporterFactory,
        SubscriberWithSalesExporterFactory $subscriberWithSalesExporterFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->customerExporterFactory = $customerExporterFactory;
        $this->guestExporterFactory = $guestExporterFactory;
        $this->orderHistoryChecker = $orderHistoryChecker;
        $this->subscriberExporterFactory = $subscriberExporterFactory;
        $this->subscriberWithSalesExporterFactory = $subscriberWithSalesExporterFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Collect for customer.
     *
     * @param Contact $contact
     * @param string|int $websiteId
     *
     * @return array
     * @throws LocalizedException
     */
    public function collectForCustomer(Contact $contact, $websiteId): array
    {
        $website = $this->storeManager->getWebsite($websiteId);

        /** @var CustomerExporter $exporter */
        $exporter = $this->customerExporterFactory->create();
        $exporter->setCsvColumns($website);

        $keyedExport = $exporter->export([$contact->getCustomerId()], $website);

        if (!isset($keyedExport[$contact->getId()])) {
            return [];
        }

        return array_combine(
            $exporter->getCsvColumns(),
            $keyedExport[$contact->getId()]
        );
    }

    /**
     * Collect for guest.
     *
     * @param Contact $contact
     * @param string|int $websiteId
     *
     * @return array
     * @throws LocalizedException
     */
    public function collectForGuest(Contact $contact, $websiteId): array
    {
        $website = $this->storeManager->getWebsite($websiteId);

        /** @var GuestExporter $exporter */
        $exporter = $this->guestExporterFactory->create();
        $exporter->setCsvColumns($website);

        $keyedExport = $exporter->export([$contact], $website);

        if (!isset($keyedExport[$contact->getId()])) {
            return [];
        }

        return array_combine(
            $exporter->getCsvColumns(),
            $keyedExport[$contact->getId()]
        );
    }

    /**
     * Collect for subscriber.
     *
     * @param Contact $contact
     * @param string|int $websiteId
     *
     * @return array
     * @throws LocalizedException
     */
    public function collectForSubscriber(Contact $contact, $websiteId): array
    {
        $isSubscriberSalesDataEnabled = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_ENABLE_SUBSCRIBER_SALES_DATA,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        $website = $this->storeManager->getWebsite($websiteId);
        $subscriberIsCustomer = $contact->getCustomerId();
        $subscribers = [$contact->getId() => $contact->getEmail()];

        if ($isSubscriberSalesDataEnabled &&
            !$subscriberIsCustomer &&
            $this->orderHistoryChecker->checkInSales($subscribers)
        ) {
            $exporter = $this->subscriberWithSalesExporterFactory->create();
        } else {
            $exporter = $this->subscriberExporterFactory->create();
        }

        /** @var AbstractExporter $exporter */
        $exporter->setCsvColumns($website);
        $keyedExport = $exporter->export($subscribers, $website);

        if (!isset($keyedExport[$contact->getId()])) {
            return [];
        }

        return array_combine(
            $exporter->getCsvColumns(),
            $keyedExport[$contact->getId()]
        );
    }

    /**
     * Merge data fields.
     *
     * Merge a set of 'new' data fields into an 'original' set,
     * preparing the data structure for an API request in the process.
     * Note that original keys are not overwritten with new values.
     *
     * @param array $originalDataFields
     * @param array $newDataFields
     *
     * @return array
     */
    public function mergeFields(array $originalDataFields, array $newDataFields): array
    {
        $combinedDataFields = $originalDataFields;
        $originalKeys = array_merge(
            array_column($originalDataFields, 'Key'),
            [
                'Email',
                'EmailType'
            ]
        );

        foreach ($newDataFields as $key => $newDataField) {
            if (in_array($key, $originalKeys)) {
                continue;
            }
            $combinedDataFields[] = [
                'Key' => $key,
                'Value' => $newDataField
            ];
        }
        return $combinedDataFields;
    }

    /**
     * Extract consent fields from data fields.
     *
     * Data fields are passed by reference. If we find a consent field, we add it
     * to a separate array and _remove_ it from the original data fields array.
     * Will return an empty array if either consent fields are present but have no values,
     * OR if there are no consent fields (i.e. it hasn't been enabled in the config).
     *
     * @param array $dataFields
     *
     * @return array
     */
    public function extractConsentFromPreparedDataFields(array &$dataFields): array
    {
        $consentFields = [];
        foreach ($dataFields as $key => $field) {
            if (in_array($field['Key'], Consent::$bulkFields)) {
                if ($field['Value']) {
                    $consentFields[] = [
                        'Key' => Consent::BULKFIELDTOSINGLEFIELDNAMEMAP[$field['Key']],
                        'Value' => $field['Value']
                    ];
                }
                unset($dataFields[$key]);
            }
        }
        return $consentFields;
    }
}
