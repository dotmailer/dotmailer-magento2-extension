<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Register new wishlist automation.
 */
class RegisterWishlist implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\WishlistFactory
     */
    private $wishlistFactory;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customer;
    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    private $automationFactory;

    /**
     * RegisterWishlist constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory           $automationFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface        $customer
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory             $wishlistFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                       $data
     * @param \Magento\Store\Model\StoreManagerInterface               $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->automationFactory = $automationFactory;
        $this->customer   = $customer;
        $this->wishlistFactory   = $wishlistFactory;
        $this->helper            = $data;
        $this->storeManager      = $storeManagerInterface;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //wishlist
        $wishlist = $observer->getEvent()->getObject();

        if ($wishlist->getCustomerId() && $wishlist->getWishlistId()) {
            $itemsCount = $wishlist->getItemsCount();
            //wishlist items found
            if ($itemsCount) {
                //save wishlist info in the table
                $this->registerWishlist($wishlist);
            }
        }
    }

    /**
     * Automation new wishlist program.
     *
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function registerWishlist($wishlist)
    {
        try {
            $emailWishlist = $this->wishlistFactory->create();

            //if wishlist exist not to save again
            if (!$emailWishlist->getWishlist($wishlist->getWishlistId())) {
                $customer = $this->customer->getById($wishlist->getCustomerId());
                $email = $customer->getEmail();
                $websiteId = $customer->getWebsiteId();
                $emailWishlist->setWishlistId($wishlist->getWishlistId())
                    ->setCustomerId($wishlist->getCustomerId())
                    ->setStoreId($customer->getStoreId());
                $emailWishlist->getResource()->save($emailWishlist);

                $store
                           = $this->storeManager->getStore($customer->getStoreId());
                $storeName = $store->getName();

                //if api is not enabled
                if (!$this->helper->isEnabled($websiteId)) {
                    return $this;
                }
                $programId
                    = $this->helper->getWebsiteConfig(
                        \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST,
                        $websiteId
                    );
                //wishlist program mapped
                if ($programId) {
                    $automation = $this->automationFactory->create();
                    //save automation type
                    $automation->setEmail($email)
                        ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_WISHLIST)
                        ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                        ->setTypeId($wishlist->getWishlistId())
                        ->setWebsiteId($websiteId)
                        ->setStoreName($storeName)
                        ->setProgramId($programId);
                    $automation->getResource()->save($automation);
                }
            }
        } catch (\Exception $e) {
            $this->helper->error((string)$e, []);
        }

        return $this;
    }
}
