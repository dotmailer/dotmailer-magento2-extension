<?php

namespace Dotdigitalgroup\Email\Block;

class Wishlist extends \Magento\Framework\View\Element\Template
{
	protected $_website;

	public $helper;
	public $priceHelper;
	public $scopeManager;
	public $objectManager;


	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
		$this->priceHelper = $priceHelper;
		$this->scopeManager = $scopeConfig;
		$this->objectManager = $objectManagerInterface;
	}

    public function getWishlistItems()
    {
        $wishlist = $this->_getWishlist();
        if($wishlist && count($wishlist->getItemCollection()))
            return $wishlist->getItemCollection();
        else
            return false;
    }

    protected function _getWishlist()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        if(!$customerId)
            return false;

        $customer = $this->objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
        if(!$customer->getId())
            return false;

        $collection = $this->objectManager->create('Magento\Wishlist\Model\Wishlist')->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId)
                    ->setOrder('updated_at', 'DESC');

        if ($collection->count())
            return $collection->getFirstItem();
        else
            return false;

    }

    public function getMode()
    {
        return $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_WIHSLIST_DISPLAY
        );
    }
}