<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class CustomerLogin implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var Data
     */
    private $helper;

    /**
     * CustomerLogin constructor.
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param ContactFactory $contactFactory
     * @param CollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager,
        ContactFactory $contactFactory,
        CollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        Data $helper
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->contactFactory = $contactFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer): CustomerLogin
    {
        $websiteId = $this->storeManager->getWebsite()->getId();

        if (!$this->helper->isEnabled($websiteId)) {
            return $this;
        }

        $storeId = $this->storeManager->getStore()->getId();
        $customerId = $observer->getEvent()->getCustomer()->getId();
        $emailAddress = $observer->getEvent()->getCustomer()->getEmail();

        try {
            $existingContact = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail($emailAddress, $websiteId);

            if ($existingContact) {
                if (!$existingContact->getCustomerId()) {
                    $existingContact->setCustomerId($customerId);
                    $this->contactResource->save($existingContact);
                }
            } else {
                $newContact = $this->contactFactory->create()
                    ->setEmail($emailAddress)
                    ->setWebsiteId($websiteId)
                    ->setEmailImported(Contact::EMAIL_CONTACT_NOT_IMPORTED)
                    ->setStoreId($storeId)
                    ->setCustomerId($customerId);
                $this->contactResource->save($newContact);
            }
        } catch (\Exception $e) {
            $this->logger->debug('Error when updating email_contact table', [(string) $e]);
        }

        return $this;
    }
}
