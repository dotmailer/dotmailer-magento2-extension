<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

class DefaultLevelCatalogSyncer implements CatalogSyncerInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalogResource;

    /**
     * @var StoreCatalogSyncer
     */
    private $storeCatalogSyncer;

    /**
     * DefaultLevelCatalogSyncer constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     * @param StoreCatalogSyncer $storeCatalogSyncer
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        StoreCatalogSyncer $storeCatalogSyncer
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->catalogResource = $catalogResource;
        $this->storeCatalogSyncer = $storeCatalogSyncer;
    }

    /**
     * Sync
     *
     * @return int
     */
    public function sync()
    {
        $enabled = $this->helper->isEnabled();
        $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled();

        if (!$enabled || !$catalogSyncEnabled) {
            return 0;
        }

        $limit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );

        $products = $this->storeCatalogSyncer->syncByStore(
            null,
            0,
            $limit,
            'Catalog_Default'
        );

        /*
         * Sits outside of syncByStore - behaviour differs at store level.
         * At store level, we gather products from all stores first, then mark them as imported,
         * to allow for products being synced with multiple stores.
         */
        $this->catalogResource->setImportedByIds(array_keys($products));

        return count($products);
    }
}
