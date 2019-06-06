<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

/**
 * Factory class for catalog sync
 */
class CatalogSyncFactory
{
    const SYNC_CATALOG_DEFAULT_LEVEL = 1;
    const SYNC_CATALOG_STORE_LEVEL = 2;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DefaultLevelCatalogSyncerFactory
     */
    private $defaultLevelCatalogSyncerFactory;

    /**
     * @var StoreLevelCatalogSyncerFactory
     */
    private $storeLevelCatalogSyncerFactory;

    /**
     * CatalogSyncFactory constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param DefaultLevelCatalogSyncerFactory $defaultLevelCatalogSyncerFactory
     * @param StoreLevelCatalogSyncerFactory $storeLevelCatalogSyncerFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        DefaultLevelCatalogSyncerFactory $defaultLevelCatalogSyncerFactory,
        StoreLevelCatalogSyncerFactory $storeLevelCatalogSyncerFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->defaultLevelCatalogSyncerFactory = $defaultLevelCatalogSyncerFactory;
        $this->storeLevelCatalogSyncerFactory = $storeLevelCatalogSyncerFactory;
    }

    /**
     * Create syncer class instance depending on configuration
     *
     * @return \Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncerInterface|null
     */
    public function create()
    {
        $syncLevel = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES
        );

        if ($syncLevel == self::SYNC_CATALOG_DEFAULT_LEVEL) {
            return $this->defaultLevelCatalogSyncerFactory->create();
        } elseif ($syncLevel == self::SYNC_CATALOG_STORE_LEVEL) {
            return $this->storeLevelCatalogSyncerFactory->create();
        }

        return null;
    }
}
