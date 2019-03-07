<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

class StoreLevelCatalogSyncer implements CatalogSyncerInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

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
     * StoreLevelCatalogSyncer constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     * @param StoreCatalogSyncer $storeCatalogSyncer
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        StoreCatalogSyncer $storeCatalogSyncer
    ) {
        $this->importerFactory = $importerFactory;
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
        $limit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );
        $stores = $this->helper->getStores();
        $products = [];

        foreach ($stores as $store) {
            $enabled = $this->helper->isEnabled($store->getWebsiteId());
            $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled($store->getWebsiteId());

            if (!$enabled || !$catalogSyncEnabled) {
                continue;
            }

            $importType = 'Catalog_' . $store->getWebsite()->getCode() . '_' . $store->getCode();
            $products += $this->storeCatalogSyncer->syncByStore(
                            $store->getId(),
                            $store->getWebsiteId(),
                            $limit,
                            $importType
            );
        }

        /*
         * Sits outside of syncByStore by intention.
         * We grab products for sync from each store and put them all into an array, then we mark them all as imported.
         * This is to prevent products that need to be synced with multiple stores being marked as imported before they have been synced.
         */
        $this->catalogResource->setImportedByIds(array_keys($products));

        return count($products);
    }
}
