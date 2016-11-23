<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Mostviewed extends \Magento\Catalog\Block\Product\AbstractProduct
{
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
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory|\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public $productFactory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public $productCollectionFactory;
    /**
     * @var \Magento\Reports\Model\ResourceModel\Product\CollectionFactory
     */
    public $reportProductCollection;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $coreResource;

    /**
     * Mostviewed constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data                             $helper
     * @param \Magento\Catalog\Block\Product\Context                         $context
     * @param \Magento\Framework\Pricing\Helper\Data                         $priceHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\ProductFactory                          $productFactory
     * @param \Dotdigitalgroup\Email\Helper\Recommended                      $recommended
     * @param \Magento\Catalog\Model\CategoryFactory                         $categtoryFactory
     * @param \Magento\Framework\App\ResourceConnection                      $resourceConnection
     * @param \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportProductCollection
     * @param array                                                          $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Model\CategoryFactory $categtoryFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportProductCollection,
        array $data = []
    ) {
        $this->coreResource             = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productFactory           = $productFactory;
        $this->categoryFactory          = $categtoryFactory;
        $this->reportProductCollection  = $reportProductCollection;
        $this->helper                   = $helper;
        $this->recommnededHelper        = $recommended;
        $this->priceHelper              = $priceHelper;

        parent::__construct($context, $data);
    }

    /**
     * Get product collection.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $productsToDisplay = [];
        $mode = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $from  = $this->recommnededHelper->getTimeFromConfig($mode);
        $to = $this->_localeDate->date()->format(\Zend_Date::ISO_8601);
        $reportProductCollection = $this->reportProductCollection->create()
            ->addViewsCount($from, $to)
            ->setPageSize($limit);

        //filter collection by category by category_id
        if ($catId = $this->getRequest()->getParam('category_id')) {
            $category = $this->categoryFactory->create()->load($catId);
            if ($category->getId()) {
                $reportProductCollection->getSelect()
                    ->joinLeft(
                        ['ccpi' => $this->coreResource->getTableName('catalog_category_product_index')],
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
        if ($catName = $this->getRequest()->getParam('category_name')) {
            $category = $this->categoryFactory->create()
                ->loadByAttribute('name', $catName);
            if ($category) {
                $reportProductCollection->getSelect()
                    ->joinLeft(
                        ['ccpi' => $this->coreResource->getTableName('catalog_category_product_index')],
                        'e.entity_id  = ccpi.product_id',
                        ['category_id']
                    )
                    ->where('ccpi.category_id =?', $category->getId());
            } else {
                $this->helper->log('Most viewed. Category name ' . $catName
                    . ' is invalid. It does not exist.');
            }
        }

        //product ids from the report product collection
        $productIds = $reportProductCollection->getColumnValues('entity_id');

        $productCollectionFactory = $this->productCollectionFactory->create();
        $productCollectionFactory->addIdFilter($productIds)
            ->addAttributeToSelect(
                ['product_url', 'name', 'store_id', 'small_image', 'price']
            );

        //product collection
        foreach ($productCollectionFactory as $_product) {
            //add only saleable products
            if ($_product->isSalable()) {
                $productsToDisplay[] = $_product;
            }
        }

        return $productsToDisplay;
    }

    /**
     * Display mode type.
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
