<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

use Magento\Store\Model\Store;

class Bestsellers extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Magento\CatalogInventory\Model\StockFactory
     */
    public $stockFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;
    /**
     * @var \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory
     */
    public $productSoldFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    public $recommnededHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;

    /**
     * Bestsellers constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                                  $helper
     * @param \Magento\Framework\App\ResourceConnection                           $resource
     * @param \Magento\Framework\Pricing\Helper\Data                              $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended                           $recommended
     * @param \Magento\Catalog\Model\CategoryFactory                              $categoryFactory
     * @param \Magento\Catalog\Block\Product\Context                              $context
     * @param \Magento\CatalogInventory\Model\StockFactory                        $stockFactory
     * @param \Magento\Catalog\Model\ProductFactory                               $productFactory
     * @param \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory
     * @param array                                                               $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\CatalogInventory\Model\StockFactory $stockFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory,
        array $data = []
    ) {
        $this->productFactory     = $productFactory;
        $this->resource           = $resource;
        $this->helper             = $helper;
        $this->priceHelper        = $priceHelper;
        $this->stockFactory       = $stockFactory;
        $this->recommnededHelper  = $recommended;
        $this->categoryFactory    = $categoryFactory;
        $this->productSoldFactory = $productSoldFactory;
        parent::__construct($context, $data);
    }

    /**
     * Collection.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        //mode param grid/list
        $mode = $this->getRequest()->getActionName();
        //limit of the products to display
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        //date range
        $from = $this->recommnededHelper->getTimeFromConfig($mode);
        $to = $this->_localeDate->date()->format(\Zend_Date::ISO_8601);
        //create report collection
        $reportProductCollection = $this->productSoldFactory->create();
        $connection = $this->resource->getConnection();
        $orderTableAliasName = $connection->quoteIdentifier('order');
        $fieldName = $orderTableAliasName . '.created_at';
        $orderTableAliasName = $connection->quoteIdentifier('order');

        $orderJoinCondition = [
            $orderTableAliasName . '.entity_id = order_items.order_id',
            $connection->quoteInto("{$orderTableAliasName}.state <> ?", \Magento\Sales\Model\Order::STATE_CANCELED),
        ];
        $orderJoinCondition[] = $this->prepareBetweenSql($fieldName, $from, $to);
        $storeId = $this->_storeManager->getStore()->getId();

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
        $productSkus = $reportProductCollection->getColumnValues('sku');

        $productCollection = $this->productFactory->create()
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('sku', ['in' => $productSkus]);

        return $productCollection;
    }

    /**
     * Display type mode.
     *
     * @return mixed|string
     */
    public function getMode()
    {
        return $this->recommnededHelper->getDisplayType();
    }

    /**
     * Prepare between sql.
     *
     * @param string $fieldName Field name with table suffix ('created_at' or 'main_table.created_at')
     * @param string $from
     * @param string $to
     * @return string Formatted sql string
     */
    public function prepareBetweenSql($fieldName, $from, $to)
    {
        $connection = $this->resource->getConnection();
        return sprintf(
            '(%s BETWEEN %s AND %s)',
            $fieldName,
            $connection->quote($from),
            $connection->quote($to)
        );
    }

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getTextForUrl($store)
    {
        /** @var Store $store */
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
