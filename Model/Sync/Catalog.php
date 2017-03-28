<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Catalog
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;
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
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public $productCollection;

    /**
     * Catalog constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  $productCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollection
     * @param \Dotdigitalgroup\Email\Model\Connector\ProductFactory           $connectorProductFactory
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Framework\App\ResourceConnection                       $resource
     * @param \Dotdigitalgroup\Email\Helper\Data                              $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface              $scopeConfig
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollection,
        \Dotdigitalgroup\Email\Model\Connector\ProductFactory $connectorProductFactory,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->productCollection        = $productCollection;
        $this->catalogCollectionFactory = $catalogCollection;
        $this->connectorProductFactory  = $connectorProductFactory;
        $this->importerFactory          = $importerFactory;
        $this->helper                   = $helper;
        $this->resource                 = $resource;
        $this->scopeConfig              = $scopeConfig;
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
            try {
                //remove product with product id set and no product
                $write = $this->resource->getConnection('core_write');
                $catalogTable = $this->resource->getTableName('email_catalog');
                $select = $write->select();
                $select->reset()
                    ->from(
                        ['c' => $catalogTable],
                        ['c.product_id']
                    )
                    ->joinLeft(
                        [
                            'e' => $this->resource->getTableName(
                                'catalog_product_entity'
                            ),
                        ],
                        'c.product_id = e.entity_id'
                    )
                    ->where('e.entity_id is NULL');
                //delete sql statement
                $deleteSql = $select->deleteFromSelect('c');
                //run query
                $write->query($deleteSql);
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
                            //@codingStandardsIgnoreStart
                            $this->countProducts += count($products);
                            //@codingStandardsIgnoreEnd
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

        if ($this->countProducts) {
            $message = '----------- Catalog sync ----------- : ' . gmdate('H:i:s', microtime(true) - $this->start) .
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
     * @return bool
     */
    public function _getProductsToExport($store, $modified = false)
    {
        $limit = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );
        $connectorCollection = $this->catalogCollectionFactory->create();

        //for modified catalog
        if ($modified) {
            $connectorCollection->addFieldToFilter(
                'modified',
                ['eq' => '1']
            );
        } else {
            $connectorCollection->addFieldToFilter(
                'imported',
                ['null' => 'true']
            );
        }
        //set limit for collection
        $connectorCollection->setPageSize($limit);
        //check number of products
        if ($connectorCollection->getSize()) {
            $productIds = $connectorCollection->getColumnValues(
                'product_id'
            );
            $productCollection = $this->productCollection->create()
                ->addAttributeToSelect('*')
                ->addStoreFilter($store)
                ->addAttributeToFilter(
                    'entity_id',
                    ['in' => $productIds]
                );

            //visibility filter
            if ($visibility = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY
            )
            ) {
                $visibility = explode(',', $visibility);
                $productCollection->addAttributeToFilter(
                    'visibility',
                    ['in' => $visibility]
                );
            }
            //type filter
            if ($type = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE
            )
            ) {
                $type = explode(',', $type);
                $productCollection->addAttributeToFilter(
                    'type_id',
                    ['in' => $type]
                );
            }

            $productCollection->addWebsiteNamesToResult()
                ->addCategoryIds()
                ->addOptionsToResult();

            return $productCollection;
        }

        return false;
    }

    /**
     * Set imported in bulk query. If modified true then set modified to null in bulk query.
     *
     * @param      $ids
     * @param bool $modified
     */
    public function _setImported($ids, $modified = false)
    {
        try {
            $coreResource = $this->resource;
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_catalog');
            $ids = implode(', ', $ids);

            if ($modified) {
                $write->update(
                    $tableName,
                    [
                    'modified' => 'null',
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                    ],
                    "product_id IN ($ids)"
                );
            } else {
                $write->update(
                    $tableName,
                    [
                    'imported' => '1',
                    'updated_at' => gmdate(
                        'Y-m-d H:i:s'
                    ),
                    ],
                    "product_id IN ($ids)"
                );
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
