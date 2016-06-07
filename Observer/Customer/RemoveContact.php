<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

class RemoveContact implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

    /**
     * RemoveContact constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory  $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data           $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_contactFactory = $contactFactory;
        $this->_importerFactory = $importerFactory;
        $this->_helper = $data;
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
        $apiEnabled = $this->_helper->isEnabled($websiteId);
        $customerSync = $this->_helper->isCustomerSyncEnabled($websiteId);

        /*
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
                $this->_helper->debug((string) $e, []);
            }
        }

        return $this;
    }
}
