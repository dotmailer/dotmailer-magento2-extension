<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class ProgramFinder
{
    /**
     * @var Logger
     */
    private $logger;

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
     * Automation constructor.
     *
     * @param Logger $logger
     * @param AutomationTypeHandler $automationTypeHandler
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Logger $logger,
        AutomationTypeHandler $automationTypeHandler,
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->automationTypeHandler = $automationTypeHandler;
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get program id.
     *
     * @param string $type
     * @param int $storeId
     * @return false|string
     */
    public function getProgramIdForType(string $type, $storeId)
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

        return false;
    }
}
