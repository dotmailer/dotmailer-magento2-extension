<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Integration;

use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;

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
     * @var Catalog
     */
    private $syncCatalog;

    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * DotdigitalConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param Logger $logger
     * @param Catalog $syncCatalog
     * @param ThemeProviderInterface $themeProvider
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        Logger $logger,
        Catalog $syncCatalog,
        ThemeProviderInterface $themeProvider
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->logger = $logger;
        $this->syncCatalog = $syncCatalog;
        $this->themeProvider = $themeProvider;
    }

    /**
     * Get Configuration based on website
     *
     * @param int $websiteId
     * @return array
     */
    public function getConfig(int $websiteId)
    {
        $configurations = [];
        $storeIds = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);

        foreach ($storeIds as $storeId) {
            try {
                $configurations[] = $this->getConfigByStore((int) $storeId);
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
     * Get configuration bu Store
     *
     * @param int $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConfigByStore(int $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $storeConfiguration = [];
        $storeConfiguration["scope"] = $store->getName();
        $storeConfiguration["catalog"] = $this->syncCatalog->getStoreCatalogName($store);
        $storeConfiguration["theme"] = $this->getThemesByStoreId($store);

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

    /**
     * Get themes by store id.
     *
     * @param StoreInterface $store
     *
     * @return string
     */
    private function getThemesByStoreId(StoreInterface $store): string
    {
        $themeCodes = [];
        $themeId = $this->scopeConfig->getValue(
            DesignInterface::XML_PATH_THEME_ID,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );
        $theme = $this->themeProvider->getThemeById($themeId);

        foreach ($theme->getInheritedThemes() as $theme) {
            $themeCodes[] = $theme->getCode();
        }

        return implode(' > ', $themeCodes);
    }
}
