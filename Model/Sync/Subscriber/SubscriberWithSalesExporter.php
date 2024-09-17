<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Api\Model\Sync\Export\ContactExporterInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\SubscriberFactory as ConnectorSubscriberFactory;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SalesDataManager;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

class SubscriberWithSalesExporter extends AbstractExporter implements ContactExporterInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Datafield
     */
    private $datafield;

    /**
     * @var ConnectorSubscriberFactory
     */
    private $connectorSubscriberFactory;

    /**
     * @var OptInTypeFinder
     */
    private $optInTypeFinder;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var SalesDataManager
     */
    private $salesDataManager;

    /**
     * @var SdkContactBuilder
     */
    private $sdkContactBuilder;

    /**
     * @var SubscriberExporterFactory
     */
    private $subscriberExporterFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array $fieldMap
     */
    private $fieldMap = [];

    /**
     * @param Logger $logger
     * @param Datafield $datafield
     * @param ConnectorSubscriberFactory $connectorSubscriberFactory
     * @param OptInTypeFinder $optInTypeFinder
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param SalesDataManager $salesDataManager
     * @param SdkContactBuilder $sdkContactBuilder
     * @param SubscriberExporterFactory $subscriberExporterFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param CsvHandler $csvHandler
     */
    public function __construct(
        Logger $logger,
        Datafield $datafield,
        ConnectorSubscriberFactory $connectorSubscriberFactory,
        OptInTypeFinder $optInTypeFinder,
        ContactCollectionFactory $contactCollectionFactory,
        SalesDataManager $salesDataManager,
        SdkContactBuilder $sdkContactBuilder,
        SubscriberExporterFactory $subscriberExporterFactory,
        ScopeConfigInterface $scopeConfig,
        CsvHandler $csvHandler
    ) {
        $this->logger = $logger;
        $this->datafield = $datafield;
        $this->connectorSubscriberFactory = $connectorSubscriberFactory;
        $this->optInTypeFinder = $optInTypeFinder;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->salesDataManager = $salesDataManager;
        $this->sdkContactBuilder = $sdkContactBuilder;
        $this->subscriberExporterFactory = $subscriberExporterFactory;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($csvHandler);
    }

    /**
     * Export subscribers with sales data.
     *
     * @param array $subscribers
     * @param WebsiteInterface $website
     * @param int $listId
     *
     * @return array<SdkContact>
     * @throws LocalizedException
     */
    public function export(array $subscribers, WebsiteInterface $website, int $listId)
    : array
    {
        $exportedData = [];
        $subscriberIds = array_keys($subscribers);
        $subscriberCollection = $this->contactCollectionFactory->create()
            ->getContactsByContactIds($subscriberIds);

        $subscriberSalesData = $this->salesDataManager->setContactSalesData(
            $subscribers,
            $website,
            $this->fieldMap
        );

        foreach ($subscriberCollection as $subscriber) {
            try {
                if (isset($subscriberSalesData[$subscriber->getEmail()])) {
                    $this->setAdditionalDataOnModel(
                        $subscriber,
                        $subscriberSalesData[$subscriber->getEmail()]
                    );
                }

                $connectorSubscriber = $this->connectorSubscriberFactory->create()
                    ->init($subscriber, $this->fieldMap)
                    ->setContactData();

                $exportedData[$subscriber->getId()] = $this->sdkContactBuilder->createSdkContact(
                    $connectorSubscriber,
                    $this->fieldMap,
                    $listId,
                    $this->optInTypeFinder->getOptInType($subscriber->getStoreId())
                );

                $subscriber->clearInstance();
            } catch (\Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Error exporting subscriber id: %d',
                        $subscriber->getId()
                    ),
                    [(string) $e]
                );
                continue;
            }
        }

        return $exportedData;
    }

    /**
     * Get fields to be exported.
     *
     * We look up mapped sales data fields in the SubscriberWithSalesExporter.
     * Saves juggling 'base' columns vs 'sales' columns in this class.
     *
     * @param WebsiteInterface $website
     *
     * @return void
     *
     * @deprecated We no longer send data using csv files.
     * @see SubscriberWithSalesExporter::setFieldMapping
     */
    public function setCsvColumns(WebsiteInterface $website)
    {
        $subscriberExporter = $this->subscriberExporterFactory->create();
        $subscriberExporter->setCsvColumns($website);

        $this->columns = $subscriberExporter->getCsvColumns() +
            $this->getColumnsForMappedSalesDataFields($website);
    }

    /**
     * Get fields to be exported.
     *
     * We look up mapped sales data fields in the SubscriberWithSalesExporter.
     * Saves juggling 'base' columns vs 'sales' columns in this class.
     *
     * @param WebsiteInterface $website
     *
     * @return void
     */
    public function setFieldMapping(WebsiteInterface $website)
    : void
    {
        $subscriberExporter = $this->subscriberExporterFactory->create();
        $subscriberExporter->setFieldMapping($website);

        $this->fieldMap = $subscriberExporter->getFieldMapping() +
            $this->getColumnsForMappedSalesDataFields($website);
    }

    /**
     * Get field mapping.
     *
     * @return array
     */
    public function getFieldMapping()
    : array
    {
        return $this->fieldMap;
    }

    /**
     * Get mapped sales data fields.
     *
     * @param WebsiteInterface $website
     *
     * @return array
     */
    private function getColumnsForMappedSalesDataFields(WebsiteInterface $website)
    {
        $subscriberDataFields = $this->datafield->getSalesDatafields();

        $mappedData = $this->scopeConfig->getValue(
            'connector_data_mapping/customer_data',
            ScopeInterface::SCOPE_WEBSITES,
            $website->getId()
        );

        $mappedData = array_intersect_key($mappedData, $subscriberDataFields);
        foreach ($mappedData as $key => $value) {
            if (!$value) {
                unset($mappedData[$key]);
            }
        }
        return $mappedData;
    }
}
