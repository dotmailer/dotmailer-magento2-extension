<?php

namespace Dotdigitalgroup\Email\Block;

class Wishlist extends \Magento\Catalog\Block\Product\AbstractProduct
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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory
     */
    public $wishlistFactory;

    /**
     * Wishlist constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlistFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Block\Product\Context  $context
     * @param \Dotdigitalgroup\Email\Helper\Data      $helper
     * @param \Magento\Framework\Pricing\Helper\Data  $priceHelper
     * @param array                                   $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\WishlistFactory $wishlistFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Block\Product\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->wishlistFactory = $wishlistFactory;
        $this->customerFactory = $customerFactory;
        $this->helper          = $helper;
        $this->priceHelper     = $priceHelper;
    }

    /**
     * Get wishlist items.
     *
     * @return mixed
     */
    public function getWishlistItems()
    {
        $wishlist = $this->_getWishlist();
        if ($wishlist && ! empty($wishlist->getItemCollection())) {
            return $wishlist->getItemCollection();
        } else {
            return false;
        }
    }

    /**
     * @return bool|\Magento\Framework\DataObject
     */
    public function _getWishlist()
    {
        $params = $this->getRequest()->getParams();
        if (! $params['customer_id'] ||
            ! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        )
        {
            $this->helper->log('Wishlist no id or valid code is set');
            return false;
        }

        $customerId = (int) $params['customer_id'];
        $customer = $this->customerFactory->create();
        $customer = $customer->getResource()->load($customer, $customerId);
        if (! $customer->getId()) {
            return false;
        }

        return $this->wishlistFactory->create()
            ->getWishlistsForCustomer($customerId);
    }

    /**
     * Wishlist display mode type.
     *
     * @return mixed
     */
    public function getMode()
    {
        return $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_WIHSLIST_DISPLAY
        );
    }

    /**
     * Product url.
     *
     * @param $store
     *
     * @return mixed
     */
    public function getTextForUrl($store)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
