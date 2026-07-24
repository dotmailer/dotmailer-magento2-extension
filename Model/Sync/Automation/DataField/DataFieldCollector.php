<?php

namespace Dotdigitalgroup\Email\Model\Sync\Automation\DataField;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigital\V3\Models\Contact\DataField;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Customer\Exporter as CustomerExporter;
use Dotdigitalgroup\Email\Model\Sync\Customer\ExporterFactory as CustomerExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Export\ExporterInterface;
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
     * @param int $listId
     *
     * @return array
     * @throws LocalizedException
     */
    public function collectForCustomer(Contact $contact, $websiteId, int $listId): array
    {
        $website = $this->storeManager->getWebsite($websiteId);

        $exporter = $this->customerExporterFactory->create();
        $exporter->setFieldMapping($website);

        $keyedExport = $exporter->export([$contact->getCustomerId()], $website, $listId);

        $contactId = $contact->getId();
        if ($contactId !== null && isset($keyedExport[$contactId])) {
            return $keyedExport[$contactId]->getDataFields()->all();
        }
        return [];
    }

    /**
     * Collect for guest.
     *
     * @param Contact $contact
     * @param string|int $websiteId
     * @param int $listId
     *
     * @return array
     * @throws LocalizedException
     */
    public function collectForGuest(Contact $contact, $websiteId, int $listId): array
    {
        $website = $this->storeManager->getWebsite($websiteId);

        /** @var GuestExporter $exporter */
        $exporter = $this->guestExporterFactory->create();
        $exporter->setFieldMapping($website);

        $keyedExport = $exporter->export([$contact], $website, $listId);

        return isset($keyedExport[$contact->getId()]) ?
            $keyedExport[$contact->getId()]->getDataFields()->all() :
            [];
    }

    /**
     * Collect for subscriber.
     *
     * @param Contact $contact
     * @param string|int $websiteId
     * @param int $listId
     *
     * @return SdkContact|null
     * @throws LocalizedException
     */
    public function collectForSubscriber(Contact $contact, $websiteId, int $listId): ?SdkContact
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

        /** @var ExporterInterface $exporter */
        $exporter->setFieldMapping($website);
        $keyedExport = $exporter->export($subscribers, $website, $listId);

        return $keyedExport[$contact->getId()] ?? null;
    }

    /**
     * Merge data fields.
     *
     * Merge a set of 'new' exported data fields into an 'original' set
     * of automation-specific data fields, preserving the structured format.
     * Note that original keys are not overwritten with new values.
     *
     * @param array $originalDataFields Array of structured data fields with 'Key' and 'Value' keys
     *                                  e.g., [['Key' => 'STORE_NAME', 'Value' => 'My Store']]
     * @param array<DataField> $newDataFields Array of DataField objects to merge
     *
     * @return array Array of structured data fields e.g., [['Key' => 'STORE_NAME', 'Value' => 'My Store'], ...]
     */
    public function mergeFields(array $originalDataFields, array $newDataFields): array
    {
        $combinedDataFields = [];
        $originalKeys = [];

        foreach ($originalDataFields as $dataField) {
            if (!is_array($dataField) || !isset($dataField['Key']) || !isset($dataField['Value'])) {
                continue;
            }

            $fieldKey = $dataField['Key'];
            $fieldValue = $dataField['Value'];

            if (!is_string($fieldKey) || $fieldKey === '') {
                continue;
            }

            $combinedDataFields[] = [
                'Key' => $fieldKey,
                'Value' => $fieldValue,
            ];
            $originalKeys[] = $fieldKey;
        }

        foreach ($newDataFields as $newDataField) {
            if (!is_object($newDataField)) {
                continue;
            }

            if (!method_exists($newDataField, 'getKey') || !method_exists($newDataField, 'getValue')) {
                continue;
            }

            $key = $newDataField->getKey();

            if (in_array($key, $originalKeys, true)) {
                continue;
            }

            if (!is_string($key) || $key === '') {
                continue;
            }

            $combinedDataFields[] = [
                'Key' => $key,
                'Value' => $newDataField->getValue(),
            ];
        }

        return $combinedDataFields;
    }

    /**
     * Flatten data fields.
     *
     * Convert a structured data fields array (with 'Key' and 'Value' keys)
     * into a simple associative array suitable for passing to SdkContact::setDataFields().
     *
     * @param array $dataFields Array of structured data fields with 'Key' and 'Value' keys
     *                          e.g., [['Key' => 'STORE_NAME', 'Value' => 'My Store']]
     *
     * @return array Simple associative array e.g., ['STORE_NAME' => 'My Store', 'CUSTOMER_ID' => '123']
     */
    public function flatten(array $dataFields): array
    {
        $flattenedDataFields = [];

        foreach ($dataFields as $dataField) {
            if (!is_array($dataField) || !isset($dataField['Key']) || !isset($dataField['Value'])) {
                continue;
            }

            $fieldKey = $dataField['Key'];
            $fieldValue = $dataField['Value'];

            if (!is_string($fieldKey) || $fieldKey === '') {
                continue;
            }

            $flattenedDataFields[$fieldKey] = $fieldValue;
        }

        return $flattenedDataFields;
    }
}
