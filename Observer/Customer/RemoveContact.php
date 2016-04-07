<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

class RemoveContact implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helper;
    protected $_registry;
    protected $_logger;
    protected $_storeManager;
    protected $_wishlistFactory;
    protected $_customerFactory;
    protected $_contactFactory;
    protected $_automationFactory;
    protected $_importerFactory;
    protected $_reviewFactory;
    protected $_wishlist;


    public function __construct(
        \Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
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
        $this->_importerFactory = $importerFactory;
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
        $customer     = $observer->getEvent()->getCustomer();
        $email        = $customer->getEmail();
        $websiteId    = $customer->getWebsiteId();
        $apiEnabled   = $this->_helper->isEnabled($websiteId);
        $customerSync = $this->_helper->getCustomerSyncEnabled($websiteId);

        /**
         * Remove contact.
         */
        if ($apiEnabled && $customerSync) {
            try {
                //register in queue with importer
                $this->_importerFactory->create()->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT,
                    $email,
                    \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_DELETE,
                    $websiteId
                );
                $contactModel = $this->_contactFactory->create()
                    ->loadByCustomerEmail($email, $websiteId);
                if ($contactModel->getId()) {
                    //remove contact
                    $contactModel->delete();
                }
            } catch (\Exception $e) {
                $this->_helper->debug((string)$e, array());
            }
        }

        return $this;
    }
}
