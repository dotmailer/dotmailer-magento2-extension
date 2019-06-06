<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Removes the contact if the customer is deleted.
 */
class RemoveContact implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * RemoveContact constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory  $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data           $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->contactFactory  = $contactFactory;
        $this->importerFactory = $importerFactory;
        $this->helper          = $data;
        $this->contactResource = $contactResource;
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
        $apiEnabled = $this->helper->isEnabled($websiteId);
        $customerSync = $this->helper->isCustomerSyncEnabled($websiteId);

        /*
         * Remove contact.
         */
        if ($apiEnabled && $customerSync) {
            try {
                //register in queue with importer
                $this->importerFactory->create()->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT,
                    $email,
                    \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_DELETE,
                    $websiteId
                );
                $contactModel = $this->contactFactory->create()
                    ->loadByCustomerEmail($email, $websiteId);
                if ($contactModel->getId()) {
                    //remove contact
                    $this->contactResource->delete($contactModel);
                }
            } catch (\Exception $e) {
                $this->helper->debug((string)$e, []);
            }
        }

        return $this;
    }
}
