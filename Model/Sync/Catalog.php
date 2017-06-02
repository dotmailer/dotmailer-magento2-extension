<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Sync account TD for catalog.
 */
class Catalog
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var
     */
    public $start;
    /**
     * @var int
     */
    public $countProducts = 0;
    /**
     * @var array
     */
    public $productIds = [];
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\ProductFactory
     */
    public $connectorProductFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    public $catalogCollectionFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    public $catalogResourceFactory;

    /**
     * Catalog constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollection
     * @param \Dotdigitalgroup\Email\Model\Connector\ProductFactory           $connectorProductFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory                    $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                              $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface              $scopeConfig
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory       $catalogResourceFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollection,
        \Dotdigitalgroup\Email\Model\Connector\ProductFactory $connectorProductFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogResourceFactory
    ) {
        $this->catalogCollectionFactory = $catalogCollection;
        $this->connectorProductFactory  = $connectorProductFactory;
        $this->importerFactory          = $importerFactory;
        $this->helper                   = $helper;
        $this->scopeConfig              = $scopeConfig;
        $this->catalogCollectionFactory = $catalogResourceFactory;
    }

    /**
     * Catalog sync.
     *
     * @return array
     */
    public function sync()
    {
        $response    = ['success' => true, 'message' => 'Done.'];
        $this->start = microtime(true);

        $enabled = $this->helper->isEnabled();
        $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled();
        //api and catalog sync enabled
        if ($enabled && $catalogSyncEnabled) {
            $this->syncCatalog();
        }

        if ($this->countProducts) {
            $message = '----------- Catalog sync ----------- : ' .
                gmdate('H:i:s', microtime(true) - $this->start) .
                ', Total synced = ' . $this->countProducts;
            $this->helper->log($message);
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Export catalog.
     *
     * @param $store
     *
     * @return array|bool
     */
    public function _exportCatalog($store)
    {
        $connectorProducts = [];
        //all products for export
        $products = $this->_getProductsToExport($store);
        //get products id's
        try {
            if ($products) {
                $this->productIds = $products->getColumnValues('entity_id');

                foreach ($products as $product) {
                    $connProduct = $this->connectorProductFactory->create()
                        ->setProduct($product);
                    $connectorProducts[] = $connProduct->expose();
                }
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $connectorProducts;
    }

    /**
     * Export in single.
     *
     * @param $store
     * @param $collectionName
     * @param $websiteId
     */
    public function _exportInSingle($store, $collectionName, $websiteId)
    {
        $this->productIds = [];
        $products         = $this->_getProductsToExport($store, true);
        if ($products) {
            foreach ($products as $product) {
                $connectorProduct = $this->connectorProductFactory->create();
                $connectorProduct->setProduct($product);
                $this->helper->log(
                    '---------- Start catalog single sync ----------'
                );

                //register in queue with importer
                $this->importerFactory->create()
                    ->registerQueue(
                        $collectionName,
                        $connectorProduct->expose(),
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                        $websiteId
                    );
                $this->productIds[] = $product->getId();
            }
        }

        if (!empty($this->productIds)) {
            $this->_setImported($this->productIds, true);
            $this->countProducts += count($this->productIds);
        }
    }

    /**
     * Get product collection to export.
     *
     * @param      $store
     * @param bool $modified
     *
     * @return mixed
     */
    public function _getProductsToExport($store, $modified = false)
    {
        $limit = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );
        return $this->catalogCollectionFactory->create()
            ->getProductsToExportByStore($store, $limit, $modified);
    }

    /**
     * Set imported in bulk query. If modified true then set modified to null in bulk query.
     *
     * @param      $ids
     * @param bool $modified
     */
    public function _setImported($ids, $modified = false)
    {
        return $this->catalogResourceFactory->create()
            ->setImportedByIds($ids, $modified);
    }

    public function syncCatalog()
    {
        try {
            //remove product with product id set and no product
            $this->catalogResourceFactory->create()
                ->removeOrphanProducts();

            $scope = $this->scopeConfig->getValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES
            );
            //if only to pull default value
            if ($scope == 1) {
                $products = $this->_exportCatalog(
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID
                );

                if ($products) {
                    //register in queue with importer
                    $this->importerFactory->create()
                        ->registerQueue(
                            'Catalog_Default',
                            $products,
                            \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                            \Magento\Store\Model\Store::DEFAULT_STORE_ID
                        );

                    //set imported
                    $this->_setImported($this->productIds);

                    //set number of product imported
                    $this->countProducts += count($products);
                }
                //using single api
                $this->_exportInSingle(
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    'Catalog_Default',
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID
                );
                //if to pull store values. will be pulled for each store
            } elseif ($scope == 2) {
                $stores = $this->helper->getStores();

                foreach ($stores as $store) {
                    $websiteCode = $store->getWebsite()->getCode();
                    $storeCode = $store->getCode();
                    $products = $this->_exportCatalog($store);
                    if ($products) {
                        //register in queue with importer
                        $this->importerFactory->create()
                            ->registerQueue(
                                'Catalog_' . $websiteCode . '_'
                                . $storeCode,
                                $products,
                                \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                                $store->getWebsite()->getId()
                            );
                        //set imported
                        $this->_setImported($this->productIds);

                        //set number of product imported
                        $this->countProducts += count($products);
                    }
                    //using single api
                    $this->_exportInSingle(
                        $store,
                        'Catalog_' . $websiteCode . '_' . $storeCode,
                        $store->getWebsite()->getId()
                    );
                }
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
