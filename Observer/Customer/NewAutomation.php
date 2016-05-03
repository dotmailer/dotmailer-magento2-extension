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

        $isSubscribed = $this->_subscriberFactory->create()
            ->loadByEmail($email)->isSubscribed();

        try {
            // fix for a multiple hit of the observer
            $emailReg = $this->_registry->registry($email . '_customer_save');
            if ($emailReg) {
                return $this;
            }
            $this->_registry->register($email . '_customer_save', $email);
            $emailBefore  = $this->_customerFactory->create()
                ->load($customer->getId())->getEmail();
            $contactModel = $this->_contactFactory->create()
                ->loadByCustomerEmail($emailBefore, $websiteId);
            //email change detection
            if ($email != $emailBefore) {
                $this->_helper->log('email change detected : ' . $email
                    . ', after : ' . $emailBefore . ', website id : '
                    . $websiteId);
                if ($this->_helper->isEnabled($websiteId)) {
                    $client = $this->_helper->getWebsiteApiClient($websiteId);
                    $subscribersAddressBook
                            = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
                        $websiteId);
                    $response
                            = $client->postContacts($emailBefore);
                    //check for matching email
                    if (isset($response->id)) {
                        if ($email != $response->email) {
                            $data = array(
                                'Email'     => $email,
                                'EmailType' => 'Html'
                            );
                            //update the contact with same id - different email
                            $client->updateContact($response->id, $data);

                        }
                        if ( ! $isSubscribed
                            && $response->status == 'Subscribed'
                        ) {
                            $client->deleteAddressBookContact($subscribersAddressBook,
                                $response->id);
                        }
                    } elseif (isset($response->message)) {
                        $this->_helper->log('Email change error : '
                            . $response->message);
                    }
                }
                $contactModel->setEmail($email);
            }
            $contactModel->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED)
                ->setCustomerId($customerId)
                ->save();
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }

        return $this;
    }
}
