<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Bestsellers extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\CatalogInventory\Model\StockFactory
     */
    protected $_stockFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;
    /**
     * @var \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory
     */
    protected $_productSoldFactory;

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
     * Bestsellers constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                                  $helper
     * @param \Magento\Framework\Pricing\Helper\Data                              $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended                           $recommended
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                         $dateTime
     * @param \Magento\Catalog\Model\CategoryFactory                              $categoryFactory
     * @param \Magento\Catalog\Block\Product\Context                              $context
     * @param \Magento\CatalogInventory\Model\StockFactory                        $stockFactory
     * @param \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory
     * @param array                                                               $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\CatalogInventory\Model\StockFactory $stockFactory,
        \Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->_dateTime = $dateTime;
        $this->priceHelper = $priceHelper;
        $this->_stockFactory = $stockFactory;
        $this->recommnededHelper = $recommended;
        $this->_categoryFactory = $categoryFactory;
        $this->_productSoldFactory = $productSoldFactory;
        parent::__construct($context, $data);
    }

    /**
     * Collection.
     * 
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $collection = [];
        $mode = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode(
            $mode
        );
        $from = $this->recommnededHelper->getTimeFromConfig($mode);
        $to = new \Zend_Date(
            $this->_localeDate->date()->getTimestamp()
        );
        $productCollection = $this->_productSoldFactory->create()
            ->addAttributeToSelect('*')
            ->addOrderedQty($from, $to->toString(\Zend_Date::ISO_8601))
            ->setOrder('ordered_qty', 'desc')
            ->setPageSize($limit);

        //filter collection by category by category_id
        if ($cat_id = $this->getRequest()->getParam('category_id')) {
            $category = $this->_categoryFactory->create()->load($cat_id);
            if ($category->getId()) {
                $productCollection->getSelect()
                    ->joinLeft(
                        array('ccpi' => 'catalog_category_product_index'),
                        'e.entity_id  = ccpi.product_id',
                        array('category_id')
                    )
                    ->where('ccpi.category_id =?', $cat_id);
            } else {
                $this->helper->log(
                    'Best seller. Category id ' . $cat_id
                    . ' is invalid. It does not exist.'
                );
            }
        }

        //filter collection by category by category_name
        if ($cat_name = $this->getRequest()->getParam('category_name')) {
            $category = $this->_categoryFactory->create()
                ->loadByAttribute('name', $cat_name);
            if ($category) {
                $productCollection->getSelect()
                    ->joinLeft(
                        array('ccpi' => 'catalog_category_product_index'),
                        'e.entity_id  = ccpi.product_id',
                        array('category_id')
                    )
                    ->where('ccpi.category_id =?', $category->getId());
            } else {
                $this->helper->log(
                    'Best seller. Category name ' . $cat_name
                    . ' is invalid. It does not exist.'
                );
            }
        }

        if ($productCollection->getSize()) {
            foreach ($productCollection as $order) {
                foreach ($order->getAllVisibleItems() as $orderItem) {
                    $collection[] = $orderItem->getProduct();
                }
            }
        }

        return $collection;
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
     * @param $store
     *
     * @return mixed
     */
    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
