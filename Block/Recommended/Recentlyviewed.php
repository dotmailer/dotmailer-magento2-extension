<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Recentlyviewed extends \Magento\Catalog\Block\Product\AbstractProduct
{
	public $helper;
	public $priceHelper;
	public $objectManager;
	public $recommnededHelper;

	protected $_sessionFactory;
	protected $_productFactory;

	public function __construct(
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Customer\Model\SessionFactory $sessionFactory,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Block\Product\Context $context,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->_sessionFactory = $sessionFactory;
		$this->helper = $helper;
		$this->recommnededHelper = $recommended;
		$this->priceHelper = $priceHelper;
		$this->storeManager = $this->_storeManager;
		$this->_productFactory = $productFactory;
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
	    $session = $this->_sessionFactory->create();
        $isLoggedIn = $session->loginById($customerId);
        $collection = $this->objectManager->create('Magento\Reports\Block\Product\Viewed');
        $items = $collection->getItemsCollection()
            ->setPageSize($limit);

        $this->helper->log('Recentlyviewed customer  : ' . $customerId . ', mode ' . $mode . ', limit : ' . $limit .
            ', items found : ' . count($items) . ', is customer logged in : ' . $isLoggedIn . ', products :' . count($productsToDisplay));
        foreach ($items as $product) {
            $product = $this->_productFactory->create()
	            ->load($product->getId());
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

	public function getTextForUrl($store)
	{
		$store = $this->_storeManager->getStore($store);
		return $store->getConfig(
				\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
		);
	}
}