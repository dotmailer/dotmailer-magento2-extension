<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

class CreateUpdateContact implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    protected $_wishlist;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

    /**
     * CreateUpdateContact constructor.
     *
     * @param \Magento\Wishlist\Model\WishlistFactory      $wishlist
     * @param \Dotdigitalgroup\Email\Model\ContactFactory  $contactFactory
     * @param \Magento\Customer\Model\CustomerFactory      $customerFactory
     * @param \Magento\Framework\Registry                  $registry
     * @param \Dotdigitalgroup\Email\Helper\Data           $data
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     */
    public function __construct(
        \Magento\Wishlist\Model\WishlistFactory $wishlist,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
    ) {
        $this->_wishlist = $wishlist;
        $this->_contactFactory = $contactFactory;
        $this->_customerFactory = $customerFactory;
        $this->_helper = $data;
        $this->_registry = $registry;
        $this->_importerFactory = $importerFactory;
    }

    /**
     * If it's configured to capture on shipment - do this.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        $email = $customer->getEmail();
        $websiteId = $customer->getWebsiteId();
        $customerId = $customer->getEntityId();
        $isSubscribed = $customer->getIsSubscribed();

        try {
            // fix for a multiple hit of the observer
            $emailReg = $this->_registry->registry($email.'_customer_save');
            if ($emailReg) {
                return $this;
            }
            $this->_registry->register($email.'_customer_save', $email);
            $emailBefore = $this->_customerFactory->create()
                ->load($customer->getId())->getEmail();
            $contactModel = $this->_contactFactory->create()
                ->loadByCustomerEmail($emailBefore, $websiteId);
            //email change detection
            if ($email != $emailBefore) {
                $this->_helper->log('email change detected : '.$email
                    .', after : '.$emailBefore.', website id : '
                    .$websiteId);

                $data = [
                    'emailBefore' => $emailBefore,
                    'email' => $email,
                    'isSubscribed' => $isSubscribed,
                ];
                $this->_importerFactory->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT_UPDATE,
                    $data,
                    \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE,
                    $websiteId
                );
            } elseif (!$emailBefore) {
                //for new contacts update email
                $contactModel->setEmail($email);
            }
            $contactModel->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED)
                ->setCustomerId($customerId)
                ->save();
        } catch (\Exception $e) {
            $this->_helper->debug((string) $e, []);
        }

        return $this;
    }
}
