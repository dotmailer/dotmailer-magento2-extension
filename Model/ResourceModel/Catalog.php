<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\Schema;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class Catalog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Reports\Model\ResourceModel\Product\Index\Collection\AbstractCollection
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
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_CATALOG_TABLE, 'id');
    }

    /**
     * Catalog constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\Product\Index\Collection $productIndexCollection
     * @param \Magento\Catalog\Model\Config $config
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportProductCollection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory
     * @param null $connectionName
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
            $productCollection = $this->productFactory->create()
                ->getCollection()
                ->addIdFilter($ids)
                ->addAttributeToSelect(
                    ['product_url', 'name', 'store_id', 'small_image', 'price']
                );

            if ($limit) {
                $productCollection->getSelect()->limit($limit);
            }
        }

        return $productCollection;
    }

    /**
     * Get product collection from ids.
     *
     * @param string $productsSku
     * @param int|bool $limit
     *
     * @return array|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductsCollectionBySku($productsSku, $limit = false)
    {
        $productCollection = [];

        if (! empty($productsSku)) {
            $productCollection = $this->productFactory->create()
                ->getCollection()
                ->addAttributeToSelect(
                    ['product_url', 'name', 'store_id', 'small_image', 'price']
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
        $reportProductCollection = $this->productSoldFactory->create();
        $connection = $this->_resources->getConnection();
        $orderTableAliasName = $connection->quoteIdentifier('order');
        $fieldName = $orderTableAliasName . '.created_at';
        $orderTableAliasName = $connection->quoteIdentifier('order');

        $orderJoinCondition = [
            $orderTableAliasName . '.entity_id = order_items.order_id',
            $connection->quoteInto(
                "{$orderTableAliasName}.state <> ?",
                \Magento\Sales\Model\Order::STATE_CANCELED
            ),
        ];
        $orderJoinCondition[] = $this->prepareBetweenSql($fieldName, $from, $to);

        $reportProductCollection->getSelect()->reset()
            ->from(
                ['order_items' => $reportProductCollection->getTable('sales_order_item')],
                ['ordered_qty' => 'SUM(order_items.qty_ordered)', 'order_items_name' => 'order_items.name']
            )->joinInner(
                ['order' => $reportProductCollection->getTable('sales_order')],
                implode(' AND ', $orderJoinCondition),
                []
            )->columns(['sku'])
            ->where('parent_item_id IS NULL')
            ->group('order_items.product_id')
            ->having('SUM(order_items.qty_ordered) > ?', 0)
            ->order('ordered_qty DESC')
            ->limit($limit);

        $reportProductCollection->setStoreIds([$storeId]);
        $productsSku = $reportProductCollection->getColumnValues('sku');

        return $this->getProductsCollectionBySku($productsSku);
    }

    /**
     * Prepare between sql.
     *
     * @param string $fieldName Field name with table suffix ('created_at' or 'main_table.created_at')
     * @param string $from
     * @param string $to
     * @return string Formatted sql string
     */
    private function prepareBetweenSql($fieldName, $from, $to)
    {
        $connection = $this->_resources->getConnection();
        return sprintf(
            '(%s BETWEEN %s AND %s)',
            $fieldName,
            $connection->quote($from),
            $connection->quote($to)
        );
    }

    /**
     * Reset for re-import.
     *
     * @param string|null $from
     * @param string|null $to
     *
     * @return int
     *
     */
    public function resetCatalog($from = null, $to = null)
    {
        $conn = $this->getConnection();
        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'imported is ?' => new \Zend_Db_Expr('not null')
            ];
        } else {
            $where = $conn->quoteInto(
                'imported is ?',
                new \Zend_Db_Expr('not null')
            );
        }
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_CATALOG_TABLE),
            [
                'imported' => new \Zend_Db_Expr('null'),
                'modified' => new \Zend_Db_Expr('null'),
            ],
            $where
        );

        return $num;
    }

    /**
     * Set imported in bulk query. If modified true then set modified to null in bulk query.
     *
     * @param array $ids
     * @param bool $modified
     *
     * @return null
     */
    public function setImportedByIds($ids, $modified = false)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_CATALOG_TABLE);

            if ($modified) {
                $coreResource->update(
                    $tableName,
                    [
                        'modified' => new \Zend_Db_Expr('null'),
                        'updated_at' => gmdate('Y-m-d H:i:s'),
                    ],
                    ["product_id IN (?)" => $ids]
                );
            } else {
                $coreResource->update(
                    $tableName,
                    [
                        'imported' => '1',
                        'updated_at' => gmdate(
                            'Y-m-d H:i:s'
                        ),
                    ],
                    ["product_id IN (?)" => $ids]
                );
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Remove product with product id set and no product
     *
     * @return null
     */
    public function removeOrphanProducts()
    {
        $write = $this->getConnection();
        $catalogTable = $this->getTable(Schema::EMAIL_CATALOG_TABLE);
        $select = $write->select();
        $select->reset()
            ->from(
                ['c' => $catalogTable],
                ['c.product_id']
            )
            ->joinLeft(
                [
                    'e' => $this->getTable(
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
    }

    /**
     * Set modified if already imported
     *
     * @param array $ids
     */
    public function setModified($ids)
    {
        $write     = $this->getConnection();
        $tableName = $this->getTable(Schema::EMAIL_CATALOG_TABLE);
        $write->update(
            $tableName,
            ['modified' => 1],
            [
                $write->quoteInto("product_id IN (?)", $ids),
                $write->quoteInto("imported = ?", 1)
            ]
        );
    }
}
