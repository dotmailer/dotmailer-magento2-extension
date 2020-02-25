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
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $wishlist = $observer->getEvent()->getWishlist();
            $customer = $this->customer->getById($wishlist->getCustomerId());

            //if api is enabled
            if ($this->helper->isEnabled($customer->getWebsiteId())) {
                $itemCount = count($wishlist->getItemCollection());
                $emailWishlist = $this->getEmailWishlistById($wishlist->getId());
                //update wishlist
                if ($emailWishlist) {
                    //update the items count and reset the sync
                    $this->updateWishlistAndReset($emailWishlist, $itemCount);
                } else {
                    //new wishlist with items
                    if ($wishlist->getCustomerId() && $wishlist->getWishlistId()) {
                        $this->registerWishlist($wishlist, $itemCount, $customer);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->helper->error((string)$e, []);
        }

        return $this;
    }

    /**
     * Register new wishlist.
     *
     * @param \Dotdigitalgroup\Email\Model\Wishlist $wishlist
     * @param int $itemCount
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     */
    private function registerWishlist($wishlist, $itemCount, $customer)
    {
        try {
            $emailWishlist = $this->wishlistFactory->create();

            //if wishlist exist not to save again
            if (! $emailWishlist->getWishlist($wishlist->getWishlistId())) {
                $storeName = $this->storeManager->getStore($customer->getStoreId())->getName();
                $emailWishlist->setWishlistId($wishlist->getWishlistId())
                    ->setCustomerId($wishlist->getCustomerId())
                    ->setStoreId($customer->getStoreId())
                    ->setItemCount($itemCount);

                $this->emailWishlistResource->save($emailWishlist);
                $this->registerWithAutomation($wishlist, $customer, $storeName);
            }
        } catch (\Exception $e) {
            $this->helper->error((string)$e, []);
        }
    }

    /**
     * @param int $wishlistId
     * @return bool|\Dotdigitalgroup\Email\Model\Wishlist
     */
    private function getEmailWishlistById($wishlistId)
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
        try {
            $originalItemCount = $emailWishlist->getItemCount();
            $emailWishlist->setItemCount($itemCount);

            //first item added to wishlist
            if ($itemCount == 1 && $originalItemCount == 0) {
                $emailWishlist->setWishlistImported(0);
            } elseif ($emailWishlist->getWishlistImported()) {
                $emailWishlist->setWishlistModified(1);
            }

            $this->emailWishlistResource->save($emailWishlist);
        } catch (\Exception $e) {
            $this->helper->error((string)$e, []);
        }
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Wishlist $wishlist
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $storeName
     */
    private function registerWithAutomation($wishlist, $customer, $storeName)
    {
        try {
            $programId = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST,
                $customer->getWebsiteId()
            );

            //wishlist program mapped
            if ($programId) {
                $automation = $this->automationFactory->create();
                //save automation type
                $automation->setEmail($customer->getEmail())
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_WISHLIST)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($wishlist->getWishlistId())
                    ->setWebsiteId($customer->getWebsiteId())
                    ->setStoreName($storeName)
                    ->setProgramId($programId);
                $this->automationResource->save($automation);
            }
        } catch (\Exception $e) {
            $this->helper->error((string)$e, []);
        }
    }
}
