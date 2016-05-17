<?php


namespace Dotdigitalgroup\Email\Observer\Customer;


class NewAutomation implements \Magento\Framework\Event\ObserverInterface
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
    protected $_subscriberFactory;


    /**
     * NewAutomation constructor.
     *
     * @param \Magento\Newsletter\Model\SubscriberFactory    $subscriberFactory
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
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
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
        $this->_subscriberFactory = $subscriberFactory;
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
        $customer   = $observer->getEvent()->getCustomer();
        $email      = $customer->getEmail();
        $websiteId  = $customer->getWebsiteId();
        $customerId = $customer->getId();
        $store = $this->_storeManager->getStore($customer->getStoreId());
        $storeName = $store->getName();
        try {

            //Api is not enabled
            $apiEnabled = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);
            if (! $apiEnabled)
                return $this;
            //Automation enrolment
            $programId = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_CUSTOMER, $websiteId);

            //new contact program mapped
            if ($programId) {
                $automation = $this->_automationFactory->create();
                //save automation type
                $automation->setEmail($email)
                    ->setAutomationType(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_CUSTOMER)
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($customerId)
                    ->setWebsiteId($websiteId)
                    ->setStoreName($storeName)
                    ->setProgramId($programId);
                $automation->save();
            }

        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }

        return $this;
    }
}
