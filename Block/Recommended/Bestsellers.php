<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Bestsellers extends \Magento\Catalog\Block\Product\AbstractProduct
{

	protected $_dateTime;
	protected $_stockFactory;
	protected $_categoryFactory;
	protected $_productSoldFactory;


	public $helper;
	public $priceHelper;
	public $scopeManager;

	protected $_localeDate;

	public function __construct(
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Dotdigitalgroup\Email\Helper\Recommended $recommended,
		\Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
		\Magento\Catalog\Model\CategoryFactory  $categoryFactory,
        \Magento\Catalog\Block\Product\Context $context,
		\Magento\CatalogInventory\Model\StockFactory $stockFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Reports\Model\ResourceModel\Product\Sold\CollectionFactory $productSoldFactory,
		array $data = []
	)
	{
		$this->_localeDate = $localeDate;
		$this->helper = $helper;
		$this->_dateTime = $dateTime;
		$this->priceHelper = $priceHelper;
		$this->_stockFactory = $stockFactory;
		$this->recommnededHelper = $recommended;
		$this->_categoryFactory = $categoryFactory;
		$this->_productSoldFactory = $productSoldFactory;
		$this->scopeManager = $scopeConfig;
		$this->storeManager = $this->_storeManager;
		parent::__construct( $context, $data );
	}

	/**
     * @todo refactor the code so it can accommodate filtering by category id and category name.
     * @todo template expect the collection returned to be of type product but returned is type of order.
     *
	 * Get product collection.
	 * @return array
	 */
	public function getLoadedProductCollection()
	{
        $collection = array();
		$mode = $this->getRequest()->getActionName();
		$limit  = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $from  =  $this->recommnededHelper->getTimeFromConfig($mode);
        $to = new \Zend_Date($this->_localeDate->date()->getTimestamp());
	    $productCollection = $this->_productSoldFactory->create()
		    ->addAttributeToSelect('*')
		    ->addOrderedQty($from, $to->tostring(\Zend_Date::ISO_8601))
            ->setOrder('ordered_qty', 'desc')
	        ->setPageSize($limit);

	    //@todo check inventory for products to display
//		$this->_stockFactory->create()
//		    ->addInStockFilterToCollection($productCollection);
	    //$productCollection->addAttributeToFilter('is_saleable', TRUE);

        //filter collection by category by category_id
        if ($cat_id = $this->getRequest()->getParam('category_id')){
            $category = $this->_categoryFactory->create()->load($cat_id);
            if ($category->getId()) {
                $productCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id  = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?',  $cat_id);
            } else {
                $this->helper->log('Best seller. Category id '. $cat_id . ' is invalid. It does not exist.');
            }
        }

        //filter collection by category by category_name
        if($cat_name = $this->getRequest()->getParam('category_name')){
            $category = $this->_categoryFactory->create()
	            ->loadByAttribute('name', $cat_name);
            if($category){
                $productCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id  = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?',  $category->getId());
            }else{
                $this->helper->log('Best seller. Category name '. $cat_name .' is invalid. It does not exist.');
            }
        }

        if($productCollection->getSize()){
            foreach($productCollection as $order){
                foreach($order->getAllVisibleItems() as $orderItem){
                    $collection[] = $orderItem->getProduct();
                }
            }
        }

        //\Zend_Debug::dump($productCollection->getFirstItem()->getAllVisibleItems()[0]->getProduct()->getName());exit;
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
	 * Price html.
	 * @param $product
	 *
	 * @return string
	 */
	public function getPriceHtml($product)
    {
        $this->setTemplate('connector/product/price.phtml');
        $this->setProduct($product);
        return $this->toHtml();
    }

	public function getTextForUrl($store)
	{
		$store = $this->_storeManager->getStore($store);
		return $store->getConfig(
				\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
		);
	}

}