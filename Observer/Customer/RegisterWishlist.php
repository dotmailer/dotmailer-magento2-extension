<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory;

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
    private $emailWishlistCollectionFactory;

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
    private $customerRepository;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    private $automationFactory;

    /**
     * @var CollectionFactory
     */
    private $wishlistItemCollectionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * RegisterWishlist constructor.
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param CollectionFactory $wishlistItemCollectionFactory
     * @param Logger $logger
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        CollectionFactory $wishlistItemCollectionFactory,
        Logger $logger
    ) {
        $this->emailWishlistCollectionFactory = $emailWishlistCollectionFactory;
        $this->automationResource = $automationResource;
        $this->emailWishlistResource = $emailWishlistResource;
        $this->automationFactory = $automationFactory;
        $this->customerRepository = $customerRepository;
        $this->wishlistFactory   = $wishlistFactory;
        $this->helper            = $data;
        $this->storeManager      = $storeManagerInterface;
        $this->wishlistItemCollectionFactory = $wishlistItemCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $wishlist = $observer->getEvent()->getWishlist();
            $website = $this->storeManager->getWebsite();
            $storeId = $this->storeManager->getStore()->getId();

            if ($this->helper->isEnabled($website->getId())) {
                $itemCount = $this->getWishlistItemCountByStoreIds(
                    $wishlist,
                    $website->getStoreIds()
                );
                $emailWishlist = $this->getEmailWishlistById($wishlist->getId(), $storeId);

                if ($emailWishlist) {
                    $this->updateWishlistAndReset($emailWishlist, $itemCount);
                } else {
                    $this->registerWishlist($wishlist, $itemCount, $website->getId(), $storeId);
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug((string) $e);
        }

        return $this;
    }

    /**
     * Register new wishlist.
     *
     * @param \Dotdigitalgroup\Email\Model\Wishlist $wishlist
     * @param int $itemCount
     * @param $websiteId
     * @param $storeId
     */
    private function registerWishlist($wishlist, $itemCount, $websiteId, $storeId)
    {
        try {
            $emailWishlist = $this->wishlistFactory->create()
                ->setWishlistId($wishlist->getWishlistId())
                ->setCustomerId($wishlist->getCustomerId())
                ->setStoreId($storeId)
                ->setItemCount($itemCount);

            $this->emailWishlistResource->save($emailWishlist);

            $this->registerWithAutomation(
                $wishlist,
                $websiteId,
                $this->storeManager->getStore($storeId)->getName()
            );

        } catch (\Exception $e) {
            $this->helper->error((string)$e, []);
        }
    }

    /**
     * @param int $wishlistId
     * @return bool|\Dotdigitalgroup\Email\Model\Wishlist
     */
    private function getEmailWishlistById($wishlistId, $storeId)
    {
        return $this->emailWishlistCollectionFactory->create()
            ->getWishlistByIdAndStoreId($wishlistId, $storeId);
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Wishlist $emailWishlist
     * @param int $itemCount
     */
    private function updateWishlistAndReset($emailWishlist, $itemCount)
    {
        try {
            $emailWishlist->setItemCount($itemCount);
            $emailWishlist->setWishlistImported(0);

            $this->emailWishlistResource->save($emailWishlist);
        } catch (\Exception $e) {
            $this->logger->debug((string) $e);
        }
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Wishlist $wishlist
     * @param string $storeName
     */
    private function registerWithAutomation($wishlist, $websiteId, $storeName)
    {
        $customer = $this->customerRepository->getById($wishlist->getCustomerId());

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
                    ->setWebsiteId($websiteId)
                    ->setStoreName($storeName)
                    ->setProgramId($programId);
                $this->automationResource->save($automation);
            }
        } catch (\Exception $e) {
            $this->helper->error((string)$e, []);
        }
    }

    /**
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param array $storeIds
     * @return int
     */
    private function getWishlistItemCountByStoreIds($wishlist, $storeIds)
    {
        $collection = $this->wishlistItemCollectionFactory->create()
            ->addWishlistFilter($wishlist)
            ->addStoreFilter([$storeIds]);

        return $collection->getSize();
    }
}
