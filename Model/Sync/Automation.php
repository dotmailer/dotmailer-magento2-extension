<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\Sender;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\PendingContact\PendingContactUpdater;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Sync automation by type.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Automation implements SyncInterface
{
    const AUTOMATION_SYNC_LIMIT = 100;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    private $automationCollectionFactory;

    /**
     * @var AutomationProcessorFactory
     */
    private $automationProcessorFactory;

    /**
     * @var AutomationTypeHandler
     */
    private $automationTypeHandler;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var PendingContactUpdater
     */
    private $pendingContactUpdater;

    /**
     * Automation constructor.
     *
     * @param Data $helper
     * @param Logger $logger
     * @param CollectionFactory $automationCollectionFactory
     * @param AutomationProcessorFactory $automationProcessorFactory
     * @param AutomationTypeHandler $automationTypeHandler
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param Sender $sender
     * @param PendingContactUpdater $pendingContactUpdater
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        CollectionFactory $automationCollectionFactory,
        AutomationProcessorFactory $automationProcessorFactory,
        AutomationTypeHandler $automationTypeHandler,
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        Sender $sender,
        PendingContactUpdater $pendingContactUpdater
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->automationCollectionFactory = $automationCollectionFactory;
        $this->automationProcessorFactory = $automationProcessorFactory;
        $this->automationTypeHandler = $automationTypeHandler;
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
        $this->sender = $sender;
        $this->pendingContactUpdater = $pendingContactUpdater;
    }

    /**
     * Sync.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @param \DateTime|null $from
     * @return void
     */
    public function sync(\DateTime $from = null)
    {
        $this->pendingContactUpdater->update();

        foreach ($this->automationTypeHandler->getAutomationTypes() as $type => $properties) {

            $collection = $this->automationCollectionFactory->create()
                ->getCollectionByType($type, self::AUTOMATION_SYNC_LIMIT);

            $data = $properties['model']->create()
                ->process($collection);

            foreach ($data as $websiteId => $websiteGroup) {
                foreach ($websiteGroup as $storeId => $storeData) {
                    $programId = $this->getProgramId($type, $storeId);
                    $this->sender->sendAutomationEnrolments(
                        $type,
                        $storeData['contacts'],
                        $websiteId,
                        $programId
                    );
                }
            }
        }
    }

    /**
     * @param string $type
     * @param int $storeId
     * @return false|string
     */
    private function getProgramId($type, $storeId)
    {
        if (strpos($type, AutomationTypeHandler::ORDER_STATUS_AUTOMATION) !== false) {
            try {
                $orderStatusAutomations = $this->serializer->unserialize(
                    $this->scopeConfig->getValue(
                        Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS,
                        ScopeInterface::SCOPE_STORES,
                        $storeId
                    )
                );

                foreach ($orderStatusAutomations as $item) {
                    if (strpos($type, $item['status']) !== false) {
                        return $item['automation'];
                    }
                }
            } catch (\InvalidArgumentException $e) {
                $this->logger->debug((string) $e);
                return false;
            }
        } else {
            return $this->scopeConfig->getValue(
                $this->automationTypeHandler->getPathFromAutomationType($type),
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        }
    }
}
