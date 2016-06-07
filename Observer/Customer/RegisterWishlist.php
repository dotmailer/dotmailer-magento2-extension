<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

class RegisterWishlist implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\WishlistFactory
     */
    protected $_wishlistFactory;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    protected $_automationFactory;
    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    protected $_wishlist;

    /**
     * RegisterWishlist constructor.
     *
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlist
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Magento\Wishlist\Model\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_wishlist = $wishlist;
        $this->_automationFactory = $automationFactory;
        $this->_customerFactory = $customerFactory;
        $this->_wishlistFactory = $wishlistFactory;
        $this->_helper = $data;
        $this->_storeManager = $storeManagerInterface;
    }

    /**
     * If it's configured to capture on shipment - do this.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //wishlist
        $wishlist = $observer->getEvent()->getObject()->getData();
        //required data for checking the new instance of wishlist with items in it.
        if (is_array($wishlist) && isset($wishlist['customer_id'])
            && isset($wishlist['wishlist_id'])
        ) {
            $wishlistModel = $this->_wishlist->create()
                ->load($wishlist['wishlist_id']);
            $itemsCount = $wishlistModel->getItemsCount();
            //wishlist items found
            if ($itemsCount) {
                //save wishlist info in the table
                $this->_registerWishlist($wishlist);
            }
        }
    }

    /**
     * Automation new wishlist program.
     *
     * @param array $wishlist
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _registerWishlist($wishlist)
    {
        try {
            $emailWishlist = $this->_wishlistFactory->create();
            $customer = $this->_customerFactory->create();

            //if wishlist exist not to save again
            if (!$emailWishlist->getWishlist($wishlist['wishlist_id'])) {
                $customer->load($wishlist['customer_id']);
                $email = $customer->getEmail();
                $wishlistId = $wishlist['wishlist_id'];
                $websiteId = $customer->getWebsiteId();
                $emailWishlist->setWishlistId($wishlistId)
                    ->setCustomerId($wishlist['customer_id'])
                    ->setStoreId($customer->getStoreId())
                    ->save();

                $store
                           = $this->_storeManager->getStore($customer->getStoreId());
                $storeName = $store->getName();

                //if api is not enabled
                if (!$this->_helper->isEnabled($websiteId)) {
                    return $this;
                }
                $programId
                    = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/wishlist_automation',
                    $websiteId);
                //wishlist program mapped
                if ($programId) {
                    $automation = $this->_automationFactory->create();
                    //save automation type
                    $automation->setEmail($email)
                        ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_WISHLIST)
                        ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                        ->setTypeId($wishlistId)
                        ->setWebsiteId($websiteId)
                        ->setStoreName($storeName)
                        ->setProgramId($programId);
                    $automation->save();
                }
            }
        } catch (\Exception $e) {
            $this->_helper->error((string)$e, []);
        }
    }
}
