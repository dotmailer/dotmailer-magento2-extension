<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Push extends \Magento\Catalog\Block\Product\AbstractProduct
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
        \Magento\Catalog\Block\Product\Context $context,
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
     * get the products to display for table
     */
    public function getLoadedProductCollection()
    {
        $productsToDisplay = array();
        $mode  = $this->getRequest()->getActionName();
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        $productIds = $this->recommnededHelper->getProductPushIds();

        $productCollection = $this->objectManager->create('Magento\Catalog\Model\Product')->getCollection()
            ->addAttributeToFilter('entity_id', array('in' => $productIds))
            ->setPageSize($limit)
        ;
        foreach ($productCollection as $_product) {
            $productId = $_product->getId();
            $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($productId);
            if($product->isSaleable())
                $productsToDisplay[] = $product;

        }

        return $productsToDisplay;

    }

	/**
	 * Display  type mode.
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
}