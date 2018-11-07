<?php

namespace Dotdigitalgroup\Email\Block;

/**
 * Wishlist block
 *
 * @api
 */
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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist
     */
    public $wishlist;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $customerResource;

    /**
     * Wishlist constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $wishlist
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $wishlist,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->wishlist = $wishlist;
        $this->customerFactory = $customerFactory;
        $this->helper          = $helper;
        $this->priceHelper     = $priceHelper;
        $this->customerResource = $customerResource;
    }

    /**
     * Get wishlist items.
     *
     * @return boolean|\Magento\Wishlist\Model\ResourceModel\Item\Collection
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
        ) {
            $this->helper->log('Wishlist no id or valid code is set');
            return false;
        }

        $customerId = (int) $params['customer_id'];
        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $customerId);
        if (! $customer->getId()) {
            return false;
        }

        return $this->wishlist->getWishlistsForCustomer($customerId);
    }

    /**
     * Wishlist display mode type.
     *
     * @return string|boolean
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
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return boolean|string
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
