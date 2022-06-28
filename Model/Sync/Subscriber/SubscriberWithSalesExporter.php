<?php

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\SubscriberFactory as ConnectorSubscriberFactory;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SalesDataManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

class SubscriberWithSalesExporter extends AbstractExporter
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
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var SalesDataManager
     */
    private $salesDataManager;

    /**
     * @var ConsentDataManager
     */
    private $consentDataManager;

    /**
     * @var SubscriberExporter
     */
    private $subscriberExporter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Logger $logger
     * @param Datafield $datafield
     * @param ConnectorSubscriberFactory $connectorSubscriberFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param SalesDataManager $salesDataManager
     * @param ConsentDataManager $consentDataManager
     * @param SubscriberExporter $subscriberExporter
     * @param ScopeConfigInterface $scopeConfig
     * @param CsvHandler $csvHandler
     */
    public function __construct(
        Logger $logger,
        Datafield $datafield,
        ConnectorSubscriberFactory $connectorSubscriberFactory,
        ContactCollectionFactory $contactCollectionFactory,
        SalesDataManager $salesDataManager,
        ConsentDataManager $consentDataManager,
        SubscriberExporter $subscriberExporter,
        ScopeConfigInterface $scopeConfig,
        CsvHandler $csvHandler
    ) {
        $this->logger = $logger;
        $this->datafield = $datafield;
        $this->connectorSubscriberFactory = $connectorSubscriberFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->salesDataManager = $salesDataManager;
        $this->consentDataManager = $consentDataManager;
        $this->subscriberExporter = $subscriberExporter;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($csvHandler);
    }

    /**
     * Export subscribers with sales data.
     *
     * @param array $subscribers
     * @param WebsiteInterface $website
     *
     * @return array
     */
    public function export(array $subscribers, WebsiteInterface $website)
    {
        $exportedData = [];
        $subscriberIds = array_keys($subscribers);
        $subscriberCollection = $this->contactCollectionFactory->create()
            ->getContactsByContactIds($subscriberIds);

        $subscriberConsentData = $this->consentDataManager->setSubscriberConsentData(
            $subscriberIds,
            $website->getId(),
            $this->columns
        );
        $subscriberSalesData = $this->salesDataManager->setContactSalesData(
            $subscribers,
            $website,
            $this->columns
        );

        foreach ($subscriberCollection as $subscriber) {
            try {
                if (isset($subscriberConsentData[$subscriber->getId()])) {
                    $this->setAdditionalDataOnModel(
                        $subscriber,
                        $subscriberConsentData[$subscriber->getId()]
                    );
                }

                if (isset($subscriberSalesData[$subscriber->getEmail()])) {
                    $this->setAdditionalDataOnModel(
                        $subscriber,
                        $subscriberSalesData[$subscriber->getEmail()]
                    );
                }

                $exportedData[$subscriber->getId()] = $this->connectorSubscriberFactory->create()
                    ->init($subscriber, $this->columns)
                    ->setContactData()
                    ->toCSVArray();

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
     */
    public function setCsvColumns(WebsiteInterface $website)
    {
        if (empty($this->subscriberExporter->getCsvColumns())) {
            $this->subscriberExporter->setCsvColumns($website);
        }

        $this->columns = $this->subscriberExporter->getCsvColumns() +
            $this->getColumnsForMappedSalesDataFields($website);
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
