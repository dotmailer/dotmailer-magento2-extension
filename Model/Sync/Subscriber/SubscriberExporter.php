<?php

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\SubscriberFactory as ConnectorSubscriberFactory;
use Dotdigitalgroup\Email\Model\Consent;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Magento\Store\Api\Data\WebsiteInterface;

class SubscriberExporter extends AbstractExporter
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ConnectorSubscriberFactory
     */
    private $connectorSubscriberFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ConsentDataManager
     */
    private $consentDataManager;

    /**
     * @param Config $config
     * @param Logger $logger
     * @param ConnectorSubscriberFactory $connectorSubscriberFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ConsentDataManager $consentDataManager
     * @param CsvHandler $csvHandler
     */
    public function __construct(
        Config $config,
        Logger $logger,
        ConnectorSubscriberFactory $connectorSubscriberFactory,
        ContactCollectionFactory $contactCollectionFactory,
        ConsentDataManager $consentDataManager,
        CsvHandler $csvHandler
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->connectorSubscriberFactory = $connectorSubscriberFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->consentDataManager = $consentDataManager;
        parent::__construct($csvHandler);
    }

    /**
     * Export subscribers
     *
     * @param array $subscribers
     * @param WebsiteInterface $website
     *
     * @return array
     */
    public function export(array $subscribers, WebsiteInterface $website)
    {
        if (empty($subscribers)) {
            return [];
        }

        $exportedData = [];
        $contactIds = array_keys($subscribers);
        $subscriberCollection = $this->contactCollectionFactory->create()
            ->getContactsByContactIds($contactIds);

        $subscriberConsentData = $this->consentDataManager->setSubscriberConsentData(
            $contactIds,
            $website->getId(),
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
        /** @var \Magento\Store\Model\Website $website */
        $subscriberDataFields = [
            'store_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME),
            'store_name_additional' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME_ADDITIONAL),
            'website_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME),
            'subscriber_status' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_SUBSCRIBER_STATUS)
        ];

        $this->columns = AbstractExporter::EMAIL_FIELDS + array_filter($subscriberDataFields);

        if ($this->isOptInTypeDouble($website)) {
            $this->columns += ['opt_in_type' => 'OptInType'];
        }

        if ($this->config->isConsentSubscriberEnabled($website->getId())) {
            $this->columns += Consent::$bulkFields;
        }
    }

    /**
     * Check if Need to Confirm is enabled.
     *
     * @param WebsiteInterface $website
     *
     * @return boolean
     */
    private function isOptInTypeDouble($website)
    {
        /** @var \Magento\Store\Model\Website $website */
        return (boolean) $website->getConfig(
            \Magento\Newsletter\Model\Subscriber::XML_PATH_CONFIRMATION_FLAG
        );
    }
}
