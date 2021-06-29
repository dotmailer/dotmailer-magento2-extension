<?php

namespace Dotdigitalgroup\Email\Model\Sync\Integration;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class DotdigitalConfig
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StoreWebsiteRelationInterface
     */
    private $storeWebsiteRelation;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * DotdigitalConfig constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->logger = $logger;
    }

    /**
     * @param $websiteId
     * @return array
     */
    public function getConfig($websiteId)
    {
        $configurations = [];
        $storeIds = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);

        foreach ($storeIds as $storeId) {
            try {
                $configurations[] = $this->getConfigByStore($storeId);
            } catch (LocalizedException $localizedException) {
                $this->logger->error(
                    sprintf("The requested store id %s was not found", $storeId),
                    [$localizedException->getMessage()]
                );
            }
        }

        return $configurations;
    }

    /**
     * @param $storeId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfigByStore($storeId)
    {
        $storeConfiguration = [];
        $storeConfiguration["scope"] = $this->storeManager->getStore($storeId)->getName();
        foreach (DotdigitalConfigInterface::CONFIGURATION_PATHS as $path) {
            $keys = explode("/", $path);
            $configValue = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            $storeConfiguration[$keys[0]][$keys[1]][$keys[2]] = (string) $configValue;
        }
        return $storeConfiguration;
    }
}
