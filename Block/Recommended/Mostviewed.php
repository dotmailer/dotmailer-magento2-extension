<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Mostviewed extends \Magento\Framework\View\Element\Template
{

	public $helper;
	public $priceHelper;
	protected $_localeDate;
	public $scopeManager;
	public $objectManager;


	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Dotdigitalgroup\Email\Helper\Recommended $recommended,
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
		$this->recommnededHelper = $recommended;
		$this->priceHelper = $priceHelper;
		$this->_localeDate = $localeDate;
		$this->scopeManager = $scopeConfig;
		$this->storeManager = $this->_storeManager;
		$this->objectManager = $objectManagerInterface;
	}
	/**
	 * Get product collection.
	 * @return array
	 */
	public function getLoadedProductCollection()
    {
        $productsToDisplay = array();
        $mode = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $from  = $this->recommnededHelper->getTimeFromConfig($mode);
		//@todo fix locale
	    $to = \Zend_Date::now()->toString(\Zend_Date::ISO_8601);
	    $productCollection = $this->objectManager->create('Magento\Reports\Model\Resource\Product\Collection')
            ->addViewsCount($from, $to)
            ->setPageSize($limit);

        //filter collection by category by category_id
        if($cat_id = $this->getRequest()->getParam('category_id')){
            $category = $this->objectManager->create('Magento\Catalog\Model\Catagtory')->load($cat_id);
            if($category->getId()){
                $productCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?',  $cat_id);
            }else{
                $this->helper->log('Most viewed. Category id '. $cat_id . ' is invalid. It does not exist.');
            }
        }

        //filter collection by category by category_name
        if($cat_name = $this->getRequest()->getParam('category_name')){
            $category = $this->objectManager->create('Magento\Catalog\Model\Catagtory')->loadByAttribute('name', $cat_name);
            if($category){
                $productCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id  = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?',  $category->getId());
            }else{
                $this->helper->log('Most viewed. Category name '. $cat_name .' is invalid. It does not exist.');
            }
        }

        foreach ($productCollection as $_product) {
            $productId = $_product->getId();
            $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($productId);
            if($product->isSalable())
                $productsToDisplay[] = $product;
        }

        return $productsToDisplay;
    }


	/**
	 * Display mode type.
	 * @return mixed|string
	 */
	public function getMode()
    {
        return $this->recommnededHelper->getDisplayType();
    }

	/**
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
}