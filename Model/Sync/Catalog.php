<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Catalog
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var
     */
    protected $_start;
    /**
     * @var int
     */
    protected $_countProducts = 0;
    /**
     * @var array
     */
    protected $_productIds = [];
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\ProductFactory
     */
    protected $_connectorProductFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    protected $_catalogCollectionFactory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollection;

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
        $this->_productCollection = $productCollection;
        $this->_catalogCollectionFactory = $catalogCollection;
        $this->_connectorProductFactory = $connectorProductFactory;
        $this->_importerFactory = $importerFactory;
        $this->_helper = $helper;
        $this->_resource = $resource;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Catalog sync.
     *
     * @return array
     */
    public function sync()
    {
        $response = ['success' => true, 'message' => 'Done.'];
        $this->_start = microtime(true);

        $enabled = $this->_helper->isEnabled();
        $catalogSyncEnabled = $this->_helper->isCatalogSyncEnabled();
        //api and catalog sync enabled
        if ($enabled && $catalogSyncEnabled) {
            try {
                $this->_helper->log('---------- Start catalog sync ----------');

                //remove product with product id set and no product
                $write = $this->_resource->getConnection('core_write');
                $catalogTable = $this->_resource->getTableName('email_catalog');
                $select = $write->select();
                $select->reset()
                    ->from(
                        ['c' => $catalogTable],
                        ['c.product_id']
                    )
                    ->joinLeft(
                        [
                            'e' => $this->_resource->getTableName(
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
                $scope = $this->_scopeConfig->getValue(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES
                );
                //if only to pull default value
                if ($scope == 1) {
                    $products = $this->_exportCatalog(
                        \Magento\Store\Model\Store::DEFAULT_STORE_ID
                    );

                    if ($products) {
                        //register in queue with importer
                        $this->_importerFactory->create()
                            ->registerQueue(
                                'Catalog_Default',
                                $products,
                                \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                                \Magento\Store\Model\Store::DEFAULT_STORE_ID
                            );

                        //set imported
                        $this->_setImported($this->_productIds);

                        //set number of product imported
                        $this->_countProducts += count($products);
                    }
                    //using single api
                    $this->_exportInSingle(
                        \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                        'Catalog_Default',
                        \Magento\Store\Model\Store::DEFAULT_STORE_ID
                    );
                    //if to pull store values. will be pulled for each store
                } elseif ($scope == 2) {
                    $stores = $this->_helper->getStores();

                    foreach ($stores as $store) {
                        $websiteCode = $store->getWebsite()->getCode();
                        $storeCode = $store->getCode();
                        $products = $this->_exportCatalog($store);
                        if ($products) {
                            //register in queue with importer
                            $this->_importerFactory->create()
                                ->registerQueue(
                                    'Catalog_' . $websiteCode . '_'
                                    . $storeCode,
                                    $products,
                                    \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                                    $store->getWebsite()->getId()
                                );
                            //set imported
                            $this->_setImported($this->_productIds);

                            //set number of product imported
                            //@codingStandardsIgnoreStart
                            $this->_countProducts += count($products);
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
                $this->_helper->debug((string)$e, []);
            }
        }

        if ($this->_countProducts) {
            $message = 'Total time for sync : ' . gmdate(
                'H:i:s',
                microtime(true) - $this->_start
            ) . ', Total synced = ' . $this->_countProducts;
            $this->_helper->log($message);
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
    protected function _exportCatalog($store)
    {
        $connectorProducts = [];
        //all products for export
        $products = $this->_getProductsToExport($store);
        //get products id's
        try {
            if ($products) {
                $this->_productIds = $products->getColumnValues('entity_id');

                foreach ($products as $product) {
                    $connProduct = $this->_connectorProductFactory->create()
                        ->setProduct($product);
                    $connectorProducts[] = $connProduct;
                }
            }
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, []);
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
    protected function _exportInSingle($store, $collectionName, $websiteId)
    {
        $this->_productIds = [];
        $products = $this->_getProductsToExport($store, true);
        if ($products) {
            foreach ($products as $product) {
                $connectorProduct = $this->_connectorProductFactory->create();
                $connectorProduct->setProduct($product);
                $this->_helper->log(
                    '---------- Start catalog single sync ----------'
                );

                //register in queue with importer
                $this->_importerFactory->create()
                    ->registerQueue(
                        $collectionName,
                        $connectorProduct,
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                        $websiteId
                    );
                $this->_productIds[] = $product->getId();
            }
        }

        if (!empty($this->_productIds)) {
            $this->_setImported($this->_productIds, true);
            $this->_countProducts += count($this->_productIds);
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
    protected function _getProductsToExport($store, $modified = false)
    {
        $limit = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );
        $connectorCollection = $this->_catalogCollectionFactory->create();

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
            $productCollection = $this->_productCollection->create()
                ->addAttributeToSelect('*')
                ->addStoreFilter($store)
                ->addAttributeToFilter(
                    'entity_id',
                    ['in' => $productIds]
                );

            //visibility filter
            if ($visibility = $this->_helper->getWebsiteConfig(
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
            if ($type = $this->_helper->getWebsiteConfig(
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
    protected function _setImported($ids, $modified = false)
    {
        try {
            $coreResource = $this->_resource;
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_catalog');
            $ids = implode(', ', $ids);

            if ($modified) {
                $write->update(
                    $tableName,
                    [
                    'modified' => new \Zend_Db_Expr('null'),
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
            $this->_helper->debug((string)$e, []);
        }
    }
}
