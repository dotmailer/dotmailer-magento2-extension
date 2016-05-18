<?php


namespace Dotdigitalgroup\Email\Observer\Customer;


class CreateUpdateContact implements \Magento\Framework\Event\ObserverInterface
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
    protected $_importerFactory;


    /**
     * CreateUpdateContact constructor.
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
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
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
        $this->_importerFactory   = $importerFactory;
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
        $customer = $observer->getEvent()->getCustomer();

        $email        = $customer->getEmail();
        $websiteId    = $customer->getWebsiteId();
        $customerId   = $customer->getEntityId();
        $isSubscribed = $customer->getIsSubscribed();

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

                $data = array(
                    'emailBefore'  => $emailBefore,
                    'email'        => $email,
                    'isSubscribed' => $isSubscribed
                );
                $this->_importerFactory->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT_UPDATE,
                    $data,
                    \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE,
                    $websiteId
                );

            } elseif ( ! $emailBefore) {
                //for new contacts update email
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
