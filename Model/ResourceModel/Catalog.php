<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Product\AttributeFactory;
use Dotdigitalgroup\Email\Model\Product\Index\Collection;
use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Reports\Model\ResourceModel\Product\CollectionFactory;
use Zend_Db_Statement_Exception;

class Catalog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Product\Index\Collection
     */
    private $productIndexcollection;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    private $productVisibility;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Magento\Reports\Model\ResourceModel\Product\CollectionFactory
     */
    private $reportProductCollection;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Reports\Block\Product\Viewed
     */
    public $viewed;

    /**
     * @var \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory
     */
    public $productSoldFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    private $categoryResource;

    /**
     * @var AttributeFactory $attributeHandler
     */
    private $attributeHandler;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * Initialize resource.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_CATALOG_TABLE, 'id');
    }

    /**
     * Catalog constructor.
     *
     * @param Context $context
     * @param Category $categoryResource
     * @param Data $helper
     * @param Collection $productIndexCollection
     * @param Config $config
     * @param Visibility $productVisibility
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $reportProductCollection
     * @param ProductFactory $productFactory
     * @param \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory
     * @param AttributeFactory $attributeHandler
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ?string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\Product\Index\Collection $productIndexCollection,
        \Magento\Catalog\Model\Config $config,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportProductCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory,
        AttributeFactory $attributeHandler,
        ProductCollectionFactory $productCollectionFactory,
        $connectionName = null
    ) {
        $this->helper                   = $helper;
        $this->productIndexcollection = $productIndexCollection;
        $this->config = $config;
        $this->productVisibility = $productVisibility;
        $this->productFactory           = $productFactory;
        $this->categoryFactory          = $categoryFactory;
        $this->reportProductCollection  = $reportProductCollection;
        $this->productSoldFactory       = $productSoldFactory;
        $this->categoryResource         = $categoryResource;
        $this->attributeHandler = $attributeHandler;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Get most viewed product collection.
     *
     * @param string $from
     * @param string $to
     * @param int $limit
     * @param int $catId
     * @param string $catName
     *
     * @return \Magento\Reports\Model\ResourceModel\Product\Collection
     */
    public function getMostViewedProductCollection($from, $to, $limit, $catId, $catName)
    {
        $reportProductCollection = $this->reportProductCollection->create()
            ->addViewsCount($from, $to)
            ->setPageSize($limit);

        //filter collection by category by category_id
        if ($catId) {
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, $catId);
            if ($category->getId()) {
                $reportProductCollection->getSelect()
                    ->joinLeft(
                        ['ccpi' => $this->getTable('catalog_category_product_index')],
                        'e.entity_id = ccpi.product_id',
                        ['category_id']
                    )
                    ->where('ccpi.category_id =?', $catId);
            } else {
                $this->helper->log(
                    'Most viewed. Category id ' . $catId
                    . ' is invalid. It does not exist.'
                );
            }
        }

        //filter collection by category by category_name
        if ($catName) {
            $category = $this->categoryFactory->create()
                ->loadByAttribute('name', $catName);
            if ($category->getId()) {
                $reportProductCollection->getSelect()
                    ->joinLeft(
                        ['ccpi' => $this->getTable('catalog_category_product_index')],
                        'e.entity_id  = ccpi.product_id',
                        ['category_id']
                    )
                    ->where('ccpi.category_id =?', $category->getId());
            } else {
                $this->helper->log(
                    'Most viewed. Category name ' . $catName
                    . ' is invalid. It does not exist.'
                );
            }
        }

        return $reportProductCollection;
    }

    /**
     * Get recently viewed.
     *
     * @param int $customerId
     * @param int $limit
     *
     * @return array
     */
    public function getRecentlyViewed($customerId, $limit)
    {
        $attributes = $this->config->getProductAttributes();

        $this->productIndexcollection->addAttributeToSelect($attributes);
        $this->productIndexcollection->setCustomerId($customerId);
        $this->productIndexcollection->addUrlRewrite()->setPageSize(
            $limit
        )->setCurPage(
            1
        );

        /* Price data is added to consider item stock status using price index */
        $collection = $this->productIndexcollection->addPriceData()
            ->addIndexFilter()
            ->setAddedAtOrder()
            ->setVisibility($this->productVisibility->getVisibleInSiteIds());

        return $collection->getColumnValues('product_id');
    }

    /**
     * Get product collection from ids.
     *
     * @param array $ids
     * @param int|bool $limit
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollectionFromIds($ids, $limit = false)
    {
        $productCollection = [];

        if (! empty($ids)) {
            $collectionAttributes = [
                'product_url',
                'name',
                'store_id',
                'price',
                'url_key'
            ];
            $mediaAttributes = $this->attributeHandler->create()
                ->getMediaImageAttributes();
            foreach ($mediaAttributes->getItems() as $item) {
                $collectionAttributes[] = $item->getAttributeCode();
            }

            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addIdFilter($ids);
            $productCollection->addAttributeToSelect(
                $collectionAttributes
            );

            if ($limit) {
                $productCollection->getSelect()->limit($limit);
            }
        }

        return $productCollection;
    }

    /**
     * Get product collection from sku.
     *
     * @param array $productsSku
     * @param int|bool $limit
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductsCollectionBySku($productsSku, $limit = false)
    {
        $productCollection = [];

        if (! empty($productsSku)) {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect(
                ['product_url', 'name', 'store_id', 'small_image', 'price', 'visibility']
            )->addFieldToFilter('sku', ['in' => $productsSku]);

            if ($limit) {
                $productCollection->getSelect()->limit($limit);
            }
        }

        return $productCollection;
    }

    /**
     * Get product collection from sku.
     *
     * Collection includes all media_image attributes.
     *
     * @param array $productsSku
     * @param int|bool $limit
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductsCollectionBySkuWithMedia($productsSku, $limit = false)
    {
        $productCollection = [];

        if (! empty($productsSku)) {
            $collectionAttributes = [
                'product_url',
                'name',
                'store_id',
                'price',
                'visibility',
                'url_key'
            ];
            $mediaAttributes = $this->attributeHandler->create()
                ->getMediaImageAttributes();
            foreach ($mediaAttributes->getItems() as $item) {
                $collectionAttributes[] = $item->getAttributeCode();
            }

            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect(
                $collectionAttributes
            )->addFieldToFilter('sku', ['in' => $productsSku]);

            if ($limit) {
                $productCollection->getSelect()->limit($limit);
            }
        }

        return $productCollection;
    }

    /**
     * Get bestseller collection.
     *
     * @param string $from
     * @param string $to
     * @param int $limit
     * @param int $storeId
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getBestsellerCollection($from, $to, $limit, $storeId)
    {
        //create report collection
        $reportProductCollection = $this->productSoldFactory->create()
            ->addOrderedQty($from, $to)
            ->setOrder('ordered_qty', 'desc')
            ->setStoreIds([$storeId])
            ->setPageSize($limit);

        $productsSku = $reportProductCollection->getColumnValues('order_items_sku');

        return $this->getProductsCollectionBySkuWithMedia($productsSku);
    }

    /**
     * Reset for re-import.
     *
     * @param string|null $from
     * @param string|null $to
     *
     * @return int
     */
    public function resetCatalog($from = null, $to = null)
    {
        $conn = $this->getConnection();
        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'last_imported_at IS NOT NULL OR processed = 1'
            ];
        } else {
            $where[] = 'last_imported_at IS NOT NULL OR processed = 1';
        }
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_CATALOG_TABLE),
            [
                'processed' => 0,
                'last_imported_at' => new \Zend_Db_Expr('null')
            ],
            $where
        );

        return $num;
    }

    /**
     * Set processed flag.
     *
     * @param array $ids
     *
     * @return void
     */
    public function setProcessedByIds($ids)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_CATALOG_TABLE);

            $coreResource->update(
                $tableName,
                [
                    'processed' => 1,
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ],
                ["product_id IN (?)" => $ids]
            );
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Set 'processed' to 0
     *
     * @param array $ids
     */
    public function setUnprocessedByIds($ids)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_CATALOG_TABLE);

            $coreResource->update(
                $tableName,
                [
                    'processed' => 0,
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ],
                ["product_id IN (?)" => $ids]
            );
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Set imported date.
     *
     * @param array $ids
     *
     * @return void
     */
    public function setImportedDateByIds($ids)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_CATALOG_TABLE);

            $coreResource->update(
                $tableName,
                [
                    'last_imported_at' => gmdate('Y-m-d H:i:s'),
                ],
                ["product_id IN (?)" => $ids]
            );
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Bulk product import.
     *
     * @param array $products
     */
    public function bulkProductImport($products)
    {
        if (! empty($products)) {
            $connection = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_CATALOG_TABLE);
            $connection->insertMultiple($tableName, $products);
        }
    }
}
