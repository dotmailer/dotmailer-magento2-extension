<?php

namespace Dotdigitalgroup\Email\Observer\Newsletter;

/**
 * Remove contact single delete.
 */
class RemoveContact implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactor
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
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
    ) {
    
        $this->contactFactory = $contactFactory;
        $this->helper = $data;
        $this->storeManager = $storeManagerInterface;
        $this->importerFactory = $importerFactory;
        $this->contactResource = $contactResource;
    }

    /**
     * Remove contact from account
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $email = $subscriber->getEmail();
        $websiteId = $this->storeManager->getStore($subscriber->getStoreId())
            ->getWebsiteId();
        $apiEnabled = $this->helper->isEnabled($websiteId);

        /*
         * Remove contact.
         */
        if ($apiEnabled) {
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
