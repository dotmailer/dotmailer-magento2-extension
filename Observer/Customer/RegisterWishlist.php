<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\WishlistFactory;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory;
use Magento\Wishlist\Model\Wishlist;

/**
 * Register new wishlist automation.
 */
class RegisterWishlist implements ObserverInterface
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
     * @var Automation
     */
    private $automationResource;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AutomationFactory
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
     * @var AutomationPublisher
     */
    private $publisher;

    /**
     * RegisterWishlist constructor.
     *
     * @param AutomationFactory $automationFactory
     * @param Automation $automationResource
     * @param CustomerRepositoryInterface $customerRepository
     * @param WishlistFactory $wishlistFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource
     * @param Data $data
     * @param StoreManagerInterface $storeManagerInterface
     * @param CollectionFactory $wishlistItemCollectionFactory
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param AutomationPublisher $publisher
     */
    public function __construct(
        AutomationFactory $automationFactory,
        Automation $automationResource,
        CustomerRepositoryInterface $customerRepository,
        WishlistFactory $wishlistFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $emailWishlistCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $emailWishlistResource,
        Data $data,
        StoreManagerInterface $storeManagerInterface,
        CollectionFactory $wishlistItemCollectionFactory,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        AutomationPublisher $publisher
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
        $this->publisher = $publisher;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            foreach ($observer->getItems() as $addedProduct) {
                $wishlist = $addedProduct->getWishlist();
                $website = $this->storeManager->getWebsite();
                $storeId = $this->storeManager->getStore()->getId();

                if ($this->helper->isEnabled($website->getId())) {
                    /** @var Website $website */
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
            }
        } catch (Exception $e) {
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

        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
     * @throws LocalizedException
     * @throws NoSuchEntityException
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

                $this->publisher->publish($automation);
            }
        } catch (Exception $e) {
            $this->logger->error((string) $e);
        }
    }

    /**
     * Get item count of a wishlist, filtered by store ids.
     *
     * @param Wishlist $wishlist
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
