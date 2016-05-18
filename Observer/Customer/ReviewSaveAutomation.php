<?php

namespace Dotdigitalgroup\Email\Observer\Customer;


class ReviewSaveAutomation implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helper;
    protected $_registry;
    protected $_logger;
    protected $_storeManager;
    protected $_wishlistFactory;
    protected $_customerFactory;
    protected $_contactFactory;
    protected $_automationFactory;
    protected $_reviewFactory;
    protected $_wishlist;


    /**
     * ReviewSaveAutomation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ReviewFactory     $reviewFactory
     * @param \Magento\Wishlist\Model\WishlistFactory        $wishlist
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory    $contactFactory
     * @param \Magento\Customer\Model\CustomerFactory        $customerFactory
     * @param \Dotdigitalgroup\Email\Model\WishlistFactory   $wishlistFactory
     * @param \Magento\Framework\Registry                    $registry
     * @param \Dotdigitalgroup\Email\Helper\Data             $data
     * @param \Psr\Log\LoggerInterface                       $loggerInterface
     * @param \Magento\Store\Model\StoreManagerInterface     $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_reviewFactory     = $reviewFactory;
        $this->_wishlist          = $wishlist;
        $this->_contactFactory    = $contactFactory;
        $this->_automationFactory = $automationFactory;
        $this->_customerFactory   = $customerFactory;
        $this->_wishlistFactory   = $wishlistFactory;
        $this->_helper            = $data;
        $this->_logger            = $loggerInterface;
        $this->_storeManager      = $storeManagerInterface;
        $this->_registry          = $registry;
    }

    /**
     * If it's configured to capture on shipment - do this
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $dataObject = $observer->getEvent()->getDataObject();

        if ($dataObject->getCustomerId()
            && $dataObject->getStatusId()
            == \Magento\Review\Model\Review::STATUS_APPROVED
        ) {
            $customerId = $dataObject->getCustomerId();
            $this->_helper->setConnectorContactToReImport($customerId);
            //save review info in the table
            $this->_registerReview($dataObject);
            $store     = $this->_storeManager->getStore($dataObject->getStoreId());
            $storeName = $store->getName();
            $website   = $this->_storeManager->getStore($store)->getWebsite();
            $customer  = $this->_customerFactory->create()
                ->load($customerId);
            //if api is not enabled
            if ( ! $this->_helper->isEnabled($website)) {
                return $this;
            }

            $programId
                = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/review_automation');
            if ($programId) {
                $automation = $this->_automationFactory->create();
                $automation->setEmail($customer->getEmail())
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_REVIEW)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($dataObject->getReviewId())
                    ->setWebsiteId($website->getId())
                    ->setStoreName($storeName)
                    ->setProgramId($programId);
                $automation->save();
            }
        }

        return $this;
    }

    /**
     * register review.
     *
     * @param $review
     */
    private function _registerReview($review)
    {
        try {
            $this->_reviewFactory->create()
                ->setReviewId($review->getReviewId())
                ->setCustomerId($review->getCustomerId())
                ->setStoreId($review->getStoreId())
                ->save();
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }
    }
}
