<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Register new wishlist automation.
 */
class RegisterWishlist implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist
     */
    private $emailWishlistResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory
     */
    private $emailWishlistCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation
     */
    private $automationResource;

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
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customer
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->emailWishlistCollection = $emailWishlistCollection;
        $this->automationResource = $automationResource;
        $this->emailWishlistResource = $emailWishlistResource;
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
        $wishlist = $observer->getEvent()->getWishlist();
        $wishlistId = $wishlist->getId();
        $itemCount = count($wishlist->getItemCollection());
        $emailWishlist = $this->getEmailWishlistBy($wishlistId);
        //update wishlist
        if ($emailWishlist) {
            //update the items count and reset the sync
            $this->updateWishlistAndReset($emailWishlist, $itemCount);
        } else {
            //new wishlist with items
            if ($wishlist->getCustomerId() && $wishlist->getWishlistId()) {
                $this->registerWishlist($wishlist, $itemCount);
            }
        }
    }

    /**
     * Register new wishlist.
     *
     * @param \Dotdigitalgroup\Email\Model\Wishlist $wishlist
     * @param int $itemCount
     * @return $this
     */
    private function registerWishlist($wishlist, $itemCount)
    {
        try {
            $emailWishlist = $this->wishlistFactory->create();
            //if wishlist exist not to save again
            $wishlistId = $wishlist->getWishlistId();

            if (! $emailWishlist->getWishlist($wishlistId)) {
                $customer = $this->customer->getById($wishlist->getCustomerId());
                $email = $customer->getEmail();
                $websiteId = $customer->getWebsiteId();
                $store = $this->storeManager->getStore($customer->getStoreId());
                $storeName = $store->getName();

                $emailWishlist->setWishlistId($wishlistId)
                    ->setCustomerId($wishlist->getCustomerId())
                    ->setStoreId($customer->getStoreId())
                    ->setItemCount($itemCount);

                $this->emailWishlistResource->save($emailWishlist);

                //if api is not enabled
                if (! $this->helper->isEnabled($websiteId)) {
                    return $this;
                }
                $programId = $this->helper->getWebsiteConfig(
                    'connector_automation/visitor_automation/wishlist_automation',
                    $websiteId
                );

                //wishlist program mapped
                if ($programId) {
                    $automation = $this->automationFactory->create();
                    //save automation type
                    $automation->setEmail($email)
                        ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_WISHLIST)
                        ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                        ->setTypeId($wishlistId)
                        ->setWebsiteId($websiteId)
                        ->setStoreName($storeName)
                        ->setProgramId($programId);
                    $this->automationResource->save($automation);
                }
            }
        } catch (\Exception $e) {
            $this->helper->error((string)$e, []);
        }

        return $this;
    }

    /**
     * @param int $wishlistId
     * @return bool|\Magento\Framework\DataObject
     */
    private function getEmailWishlistBy($wishlistId)
    {
         return $this->emailWishlistCollection->create()
            ->getWishlistById($wishlistId);
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Wishlist $emailWishlist
     * @param int $itemCount
     */
    private function updateWishlistAndReset($emailWishlist, $itemCount)
    {
        if ($emailWishlist) {
            try {
                $originalItemCount = $emailWishlist->getItemCount();
                $emailWishlist->setItemCount($itemCount);

                //first item added to wishlist
                if ($itemCount == 1 && $originalItemCount == 0) {
                    $emailWishlist->setWishlistImported(null);
                } elseif ($emailWishlist->getWishlistImported()) {
                    $emailWishlist->setWishlistModified(1);
                }

                $this->emailWishlistResource->save($emailWishlist);
            } catch (\Exception $e) {
                $this->helper->error((string)$e, []);
            }
        }
    }
}
