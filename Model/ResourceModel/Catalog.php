<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Catalog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
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
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_catalog', 'id');
    }

    /**
     * Catalog constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportProductCollection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Reports\Block\Product\Viewed $viewed
     * @param \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportProductCollection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Reports\Block\Product\Viewed $viewed,
        \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory,
        $connectionName = null
    )
    {
        $this->helper                   = $helper;
        $this->productFactory           = $productFactory;
        $this->categoryFactory          = $categoryFactory;
        $this->reportProductCollection  = $reportProductCollection;
        $this->viewed                   = $viewed;
        $this->productSoldFactory       = $productSoldFactory;
        parent::__construct(
            $context,
            $connectionName
        );
    }

    /**
     * Get most viewed product collection
     *
     * @param $from
     * @param $to
     * @param $limit
     * @param $catId
     * @param $catName
     * @return mixed
     */
    public function getMostViewedProductCollection($from, $to, $limit, $catId, $catName)
    {
        $reportProductCollection = $this->reportProductCollection->create()
            ->addViewsCount($from, $to)
            ->setPageSize($limit);

        //filter collection by category by category_id
        if ($catId) {
            $category = $this->categoryFactory->create();
            $category = $category->getResource()->load($category, $catId);
            if ($category->getId()) {
                $reportProductCollection->getSelect()
                    ->joinLeft(
                        ['ccpi' => $this->_resources->getTableName('catalog_category_product_index')],
                        'e.entity_id = ccpi.product_id',
                        ['category_id']
                    )
                    ->where('ccpi.category_id =?', $catId);
            } else {
                $this->helper->log('Most viewed. Category id ' . $catId
                    . ' is invalid. It does not exist.');
            }
        }

        //filter collection by category by category_name
        if ($catName) {
            $category = $this->categoryFactory->create()
                ->loadByAttribute('name', $catName);
            if ($category->getId()) {
                $reportProductCollection->getSelect()
                    ->joinLeft(
                        ['ccpi' => $this->_resources->getTableName('catalog_category_product_index')],
                        'e.entity_id  = ccpi.product_id',
                        ['category_id']
                    )
                    ->where('ccpi.category_id =?', $category->getId());
            } else {
                $this->helper->log('Most viewed. Category name ' . $catName
                    . ' is invalid. It does not exist.');
            }
        }

        return $reportProductCollection;
    }

    /**
     * Get recently viewed
     *
     * @param $limit
     * @return array
     */
    public function getRecentlyViewed($limit)
    {
        $collection = $this->viewed;
        $productItems = $collection->getItemsCollection()
            ->setPageSize($limit);

        return $productItems->getColumnValues('product_id');
    }

    /**
     * Get product collection from ids
     *
     * @param $ids
     * @param $limit
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

            if($limit) {
                $productCollection->getSelect()->limit($limit);
            }
        }

        return $productCollection;
    }

    /**
     * Get product collection from ids
     *
     * @param $productsSku
     * @param $limit
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

            if($limit) {
                $productCollection->getSelect()->limit($limit);
            }
        }

        return $productCollection;
    }

    /**
     * Get bestseller collection
     *
     * @param $from
     * @param $to
     * @param $limit
     * @param $storeId
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
     * @param null $from
     * @param null $to
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
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
        try {
            $num = $conn->update(
                $conn->getTableName('email_catalog'),
                [
                    'imported' => new \Zend_Db_Expr('null'),
                    'modified' => new \Zend_Db_Expr('null'),
                ],
                $where
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Set imported in bulk query. If modified true then set modified to null in bulk query.
     *
     * @param      $ids
     * @param bool $modified
     */
    public function setImportedByIds($ids, $modified = false)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $coreResource->getTableName('email_catalog');
            $ids = implode(', ', $ids);

            if ($modified) {
                $coreResource->update(
                    $tableName,
                    [
                        'modified' => 'null',
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
     */
    public function removeOrphanProducts()
    {
        $write = $this->getConnection();
        $catalogTable = $write->getTableName('email_catalog');
        $select = $write->select();
        $select->reset()
            ->from(
                ['c' => $catalogTable],
                ['c.product_id']
            )
            ->joinLeft(
                [
                    'e' => $write->getTableName(
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
}
