<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Customer;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Api\Model\Sync\Export\ContactExporterInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\CustomerFactory as ConnectorCustomerFactory;
use Dotdigitalgroup\Email\Model\Customer\CustomerDataFieldProviderFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\BrandAttributeFinder;
use Dotdigitalgroup\Email\Model\Sync\Export\CategoryNameFinder;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SalesDataManager;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

class Exporter extends AbstractExporter implements ContactExporterInterface
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
     * @var CustomerDataManager
     */
    private $customerDataManager;

    /**
     * @var BrandAttributeFinder
     */
    private $brandAttributeFinder;

    /**
     * @var CategoryNameFinder
     */
    private $categoryNameFinder;

    /**
     * @var SdkContactBuilder
     */
    private $sdkContactBuilder;

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
     * @var array $fieldMap
     */
    private $fieldMap = [];

    /**
     * Exporter constructor.
     *
     * @param Logger $logger
     * @param ConnectorCustomerFactory $connectorCustomerFactory
     * @param CustomerDataFieldProviderFactory $customerDataFieldProviderFactory
     * @param CustomerDataManager $customerDataManager
     * @param BrandAttributeFinder $brandAttributeFinder
     * @param CategoryNameFinder $categoryNameFinder
     * @param CsvHandler $csvHandler
     * @param SdkContactBuilder $sdkContactBuilder
     * @param SalesDataManager $salesDataManager
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Logger $logger,
        ConnectorCustomerFactory $connectorCustomerFactory,
        CustomerDataFieldProviderFactory $customerDataFieldProviderFactory,
        CustomerDataManager $customerDataManager,
        BrandAttributeFinder $brandAttributeFinder,
        CategoryNameFinder $categoryNameFinder,
        CsvHandler $csvHandler,
        SdkContactBuilder $sdkContactBuilder,
        SalesDataManager $salesDataManager,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->connectorCustomerFactory = $connectorCustomerFactory;
        $this->customerDataFieldProviderFactory = $customerDataFieldProviderFactory;
        $this->customerDataManager = $customerDataManager;
        $this->brandAttributeFinder = $brandAttributeFinder;
        $this->categoryNameFinder = $categoryNameFinder;
        $this->sdkContactBuilder = $sdkContactBuilder;
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
     * @param int $listId
     *
     * @return array<SdkContact>
     * @throws LocalizedException
     */
    public function export(array $customerIds, WebsiteInterface $website, int $listId): array
    {
        $exportedData = [];
        $customerCollection = $this->customerDataManager->buildCustomerCollection($customerIds);

        if ($customerCollection->getSize() === 0) {
            return $exportedData;
        }

        $customerScopeData = $this->customerDataManager->setCustomerScopeData($customerIds, $website->getId());
        $customerLoginData = $this->customerDataManager->fetchLastLoggedInDates($customerIds, $this->fieldMap);
        $customerReviewData = $this->customerDataManager->fetchReviewData($customerIds, $this->fieldMap);

        $productCategoryData = $this->categoryNameFinder->getCategoryNamesByStore($website, $this->fieldMap);
        $brandAttribute = $this->brandAttributeFinder->getBrandAttribute($website->getId());

        $customerSalesData = $this->salesDataManager->setContactSalesData(
            $this->getEmailsFromCollection($customerCollection),
            $website,
            $this->fieldMap
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

                if (isset($customerReviewData[$customer->getId()])) {
                    $this->setAdditionalDataOnModel(
                        $customer,
                        $customerReviewData[$customer->getId()]
                    );
                }

                if (isset($customerSalesData[$customer->getEmail()])) {
                    $this->setAdditionalDataOnModel(
                        $customer,
                        $customerSalesData[$customer->getEmail()]
                    );
                }

                $connectorCustomer = $this->connectorCustomerFactory->create()
                    ->init(
                        $customer,
                        $this->fieldMap,
                        $productCategoryData,
                        $brandAttribute
                    );

                $exportedData[$customer->getEmailContactId()] = $this->sdkContactBuilder->createSdkContact(
                    $connectorCustomer,
                    $this->fieldMap,
                    $listId
                );

                $customerCollection->removeItemByKey($customer->getId());
                unset($connectorCustomer);
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

        unset($customerScopeData);
        unset($customerLoginData);
        unset($productCategoryData);
        unset($customerSalesData);
        $customerCollection->clear();

        return $exportedData;
    }

    /**
     * Set fields to be exported.
     *
     * @param WebsiteInterface $website
     *
     * @deprecated We no longer send data using csv files.
     * @see Exporter::setFieldMapping
     */
    public function setCsvColumns(WebsiteInterface $website)
    {
        $customerDataFields = $this->customerDataFieldProviderFactory
            ->create(['data' => ['website' => $website]])
            ->addIgnoredField('subscriber_status')
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
     * Set fields to be exported.
     *
     * @param WebsiteInterface $website
     */
    public function setFieldMapping(WebsiteInterface $website): void
    {
        $customerDataFields = $this->customerDataFieldProviderFactory
            ->create(['data' => ['website' => $website]])
            ->addIgnoredField('subscriber_status')
            ->getCustomerDataFields();

        $customAttributes = $this->getCustomAttributes($website->getId());
        $attributeColumns = array_combine(
            array_column($customAttributes, 'attribute'),
            array_column($customAttributes, 'datafield')
        );

        $this->fieldMap = $attributeColumns ? $customerDataFields + $attributeColumns : $customerDataFields;
    }

    /**
     * Get field mapping.
     *
     * @return array
     */
    public function getFieldMapping(): array
    {
        return $this->fieldMap;
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
