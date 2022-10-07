<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * RegisterWishlist constructor.
     *
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
     * @param ScopeConfigInterface $scopeConfig
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
        Logger $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->emailWishlistCollectionFactory = $emailWishlistCollectionFactory;
        $this->automationResource = $automationResource;
        $this->emailWishlistResource = $emailWishlistResource;
        $this->automationFactory = $automationFactory;
        $this->customerRepository = $customerRepository;
        $this->wishlistFactory   = $wishlistFactory;
        $this->helper = $data;
        $this->storeManager = $storeManagerInterface;
        $this->wishlistItemCollectionFactory = $wishlistItemCollectionFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute.
     *
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
                /** @var \Magento\Store\Model\Website $website */
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
     * @param string|int $websiteId
     * @param string|int $storeId
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
                $storeId,
                $this->storeManager->getStore($storeId)->getName()
            );

        } catch (\Exception $e) {
            $this->logger->error((string) $e);
        }
    }

    /**
     * Get Dotdigital wishlist by id.
     *
     * @param string|int $wishlistId
     * @param string|int $storeId
     * @return bool|\Dotdigitalgroup\Email\Model\Wishlist
     */
    private function getEmailWishlistById($wishlistId, $storeId)
    {
        return $this->emailWishlistCollectionFactory->create()
            ->getWishlistByIdAndStoreId($wishlistId, $storeId);
    }

    /**
     * Update wishlist and reset for sync.
     *
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
     * Create a wishlist automation.
     *
     * @param \Dotdigitalgroup\Email\Model\Wishlist $wishlist
     * @param string|int $websiteId
     * @param string|int $storeId
     * @param string $storeName
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function registerWithAutomation($wishlist, $websiteId, $storeId, $storeName)
    {
        $customer = $this->customerRepository->getById($wishlist->getCustomerId());

        try {
            $programId = $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_WISHLIST,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            //wishlist program mapped
            if ($programId) {
                $automation = $this->automationFactory->create();
                //save automation type
                $automation->setEmail($customer->getEmail())
                    ->setAutomationType(AutomationTypeHandler::AUTOMATION_TYPE_NEW_WISHLIST)
                    ->setEnrolmentStatus(StatusInterface::PENDING)
                    ->setTypeId($wishlist->getWishlistId())
                    ->setStoreId($storeId)
                    ->setWebsiteId($websiteId)
                    ->setStoreName($storeName)
                    ->setProgramId($programId);
                $this->automationResource->save($automation);
            }
        } catch (\Exception $e) {
            $this->logger->error((string) $e);
        }
    }

    /**
     * Get item count of a wishlist, filtered by store ids.
     *
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
