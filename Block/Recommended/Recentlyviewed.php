<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Recentlyviewed extends \Magento\Framework\View\Element\Template
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
	 * Products collection.
	 *
	 * @return array
	 */
	public function getLoadedProductCollection()
    {
        $productsToDisplay = array();
        $mode = $this->getRequest()->getActionName();
        $customerId = $this->getRequest()->getParam('customer_id');
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
        //login customer to receive the recent products
	    $session = $this->objectManager->create('Magento\Customer\Model\Session');
        $isLoggedIn = $session->loginById($customerId);
        $collection = $this->objectManager->create('Magento\Reports\Block\Product\Viewed');
        $items = $collection->getItemsCollection()
            ->setPageSize($limit);

        $this->helper->log('Recentlyviewed customer  : ' . $customerId . ', mode ' . $mode . ', limit : ' . $limit .
            ', items found : ' . count($items) . ', is customer logged in : ' . $isLoggedIn . ', products :' . count($productsToDisplay));
        foreach ($items as $product) {
            $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
            if($product->isSalable())
                $productsToDisplay[$product->getId()] = $product;

        }
        $session->logout();

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