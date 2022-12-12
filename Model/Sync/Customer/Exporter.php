<?php

namespace Dotdigitalgroup\Email\Model\Sync\Customer;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\CustomerFactory as ConnectorCustomerFactory;
use Dotdigitalgroup\Email\Model\Customer\CustomerDataFieldProviderFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SalesDataManager;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

class Exporter extends AbstractExporter
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ConnectorCustomerFactory
     */
    private $connectorCustomerFactory;

    /**
     * @var CustomerDataFieldProviderFactory
     */
    private $customerDataFieldProviderFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var CustomerDataManager
     */
    private $customerDataManager;

    /**
     * @var SalesDataManager
     */
    private $salesDataManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Exporter constructor.
     *
     * @param Logger $logger
     * @param ConnectorCustomerFactory $connectorCustomerFactory
     * @param CustomerDataFieldProviderFactory $customerDataFieldProviderFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param CustomerDataManager $customerDataManager
     * @param CsvHandler $csvHandler
     * @param SalesDataManager $salesDataManager
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Logger $logger,
        ConnectorCustomerFactory $connectorCustomerFactory,
        CustomerDataFieldProviderFactory $customerDataFieldProviderFactory,
        ContactCollectionFactory $contactCollectionFactory,
        CustomerDataManager $customerDataManager,
        CsvHandler $csvHandler,
        SalesDataManager $salesDataManager,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->connectorCustomerFactory = $connectorCustomerFactory;
        $this->customerDataFieldProviderFactory = $customerDataFieldProviderFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->customerDataManager = $customerDataManager;
        $this->salesDataManager = $salesDataManager;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        parent::__construct($csvHandler);
    }

    /**
     * Gather contact data and return as an array.
     *
     * @param array $customerIds
     * @param WebsiteInterface $website
     *
     * @return array
     * @throws LocalizedException
     */
    public function export(array $customerIds, WebsiteInterface $website)
    {
        $exportedData = [];
        $customerCollection = $this->customerDataManager->buildCustomerCollection($customerIds);

        $customerScopeData = $this->customerDataManager->setCustomerScopeData($customerIds, $website->getId());
        $customerLoginData = $this->customerDataManager->fetchLastLoggedInDates($customerIds, $this->columns);

        $customerSalesData = $this->salesDataManager->setContactSalesData(
            $this->getEmailsFromCollection($customerCollection),
            $website,
            $this->columns
        );

        foreach ($customerCollection as $customer) {
            try {
                if (isset($customerScopeData[$customer->getId()])) {
                    $this->setAdditionalDataOnModel(
                        $customer,
                        $customerScopeData[$customer->getId()]
                    );
                }

                if (isset($customerLoginData[$customer->getId()])) {
                    $this->setAdditionalDataOnModel(
                        $customer,
                        $customerLoginData[$customer->getId()]
                    );
                }

                if (isset($customerSalesData[$customer->getEmail()])) {
                    $this->setAdditionalDataOnModel(
                        $customer,
                        $customerSalesData[$customer->getEmail()]
                    );
                }

                $exportedData[$customer->getEmailContactId()] = $this->connectorCustomerFactory->create()
                    ->init($customer, $this->columns)
                    ->toCSVArray();

                //clear collection and free memory
                $customer->clearInstance();
            } catch (\Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Error exporting customer id: %d',
                        $customer->getId()
                    ),
                    [(string) $e]
                );
                continue;
            }
        }

        return $exportedData;
    }

    /**
     * Set fields to be exported.
     *
     * @param WebsiteInterface $website
     */
    public function setCsvColumns(WebsiteInterface $website)
    {
        $customerDataFields = $this->customerDataFieldProviderFactory
            ->create(['data' => ['website' => $website]])
            ->getCustomerDataFields();

        $customAttributes = $this->getCustomAttributes($website->getId());
        $attributeColumns = array_combine(
            array_column($customAttributes, 'attribute'),
            array_column($customAttributes, 'datafield')
        );

        $columns = AbstractExporter::EMAIL_FIELDS + $customerDataFields;
        $this->columns = $attributeColumns ? $columns + $attributeColumns : $columns;
    }

    /**
     * Get emails from Customer collection.
     *
     * @param CustomerCollection $collection
     *
     * @return array
     */
    private function getEmailsFromCollection(CustomerCollection $collection)
    {
        return $collection->getColumnValues('email');
    }

    /**
     * Get custom attributes selected for sync.
     *
     * @param int $websiteId
     *
     * @return array|mixed
     */
    private function getCustomAttributes($websiteId)
    {
        $attr = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        if (!$attr) {
            return [];
        }

        try {
            return $this->serializer->unserialize($attr);
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug((string) $e);
            return [];
        }
    }
}
