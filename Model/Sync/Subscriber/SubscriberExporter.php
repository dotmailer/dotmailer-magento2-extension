<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Subscriber;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Api\Model\Sync\Export\ContactExporterInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\StatusInterface;
use Dotdigitalgroup\Email\Model\Connector\ContactData\SubscriberFactory as ConnectorSubscriberFactory;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder;
use Magento\Store\Api\Data\WebsiteInterface;

class SubscriberExporter extends AbstractExporter implements ContactExporterInterface
{
    /**
     * @var Logger
     */
    private $logger;

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
     * @var SdkContactBuilder
     */
    private $sdkContactBuilder;

    /**
     * @var array $fieldMap
     */
    private $fieldMap = [];

    /**
     * @param Logger $logger
     * @param ConnectorSubscriberFactory $connectorSubscriberFactory
     * @param OptInTypeFinder $optInTypeFinder
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param CsvHandler $csvHandler
     * @param SdkContactBuilder $sdkContactBuilder
     */
    public function __construct(
        Logger $logger,
        ConnectorSubscriberFactory $connectorSubscriberFactory,
        OptInTypeFinder $optInTypeFinder,
        ContactCollectionFactory $contactCollectionFactory,
        CsvHandler $csvHandler,
        SdkContactBuilder $sdkContactBuilder
    ) {
        $this->logger = $logger;
        $this->connectorSubscriberFactory = $connectorSubscriberFactory;
        $this->optInTypeFinder = $optInTypeFinder;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->sdkContactBuilder = $sdkContactBuilder;
        parent::__construct($csvHandler);
    }

    /**
     * Export subscribers
     *
     * @param array $subscribers
     * @param WebsiteInterface $website
     * @param int $listId
     *
     * @return array<SdkContact>
     */
    public function export(array $subscribers, WebsiteInterface $website, int $listId)
    : array
    {
        if (empty($subscribers)) {
            return [];
        }

        $exportedData = [];
        $contactIds = array_keys($subscribers);
        $subscriberCollection = $this->contactCollectionFactory->create()
            ->getContactsByContactIds($contactIds);

        foreach ($subscriberCollection as $subscriber) {
            try {
                $connectorSubscriber = $this->connectorSubscriberFactory->create()
                    ->init($subscriber, $this->fieldMap)
                    ->setContactData();

                $exportedData[$subscriber->getId()] = $this->sdkContactBuilder->createSdkContact(
                    $connectorSubscriber,
                    $this->fieldMap,
                    $listId,
                    $this->optInTypeFinder->getOptInType($subscriber->getStoreId()),
                    StatusInterface::SUBSCRIBED
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
     * @see SubscriberExporter::setFieldMapping
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
        /** @var \Magento\Store\Model\Website $website */
        $subscriberDataFields = [
            'store_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME),
            'store_name_additional' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME_ADDITIONAL),
            'website_name' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME),
            'subscriber_status' => $website->getConfig(Config::XML_PATH_CONNECTOR_CUSTOMER_SUBSCRIBER_STATUS)
        ];

        $this->fieldMap = array_filter($subscriberDataFields);
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
     * Check if Need to Confirm is enabled.
     *
     * @param WebsiteInterface $website
     *
     * @return boolean
     *
     * @deprecated OptInType is not a data field.
     * @see \Dotdigitalgroup\Email\Model\Sync\Subscriber::loopByWebsite()
     */
    private function isOptInTypeDouble($website)
    {
        /** @var \Magento\Store\Model\Website $website */
        return (boolean) $website->getConfig(
            \Magento\Newsletter\Model\Subscriber::XML_PATH_CONFIRMATION_FLAG
        );
    }
}
