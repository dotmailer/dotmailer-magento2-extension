<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Importer;

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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->contactFactory = $contactFactory;
        $this->importerFactory = $importerFactory;
        $this->helper = $data;
        $this->contactResource = $contactResource;
        $this->storeManager = $storeManagerInterface;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        switch ($observer->getEvent()->getName()) {
            case 'newsletter_subscriber_delete_after':
                $user = $observer->getEvent()->getSubscriber();
                $customerId = (int) $user->getCustomerId();
                $websiteId = (int) $this->storeManager->getStore($user->getStoreId())->getWebsiteId();
                $shouldDelete = $customerId === 0 || $this->helper->isOnlySubscribersForContactSync($websiteId);
                break;

            default:
                $user = $observer->getEvent()->getCustomer();
                $websiteId = $user->getWebsiteId();
                $shouldDelete = true;
        }

        if ($this->helper->isEnabled($websiteId) && $this->helper->isCustomerSyncEnabled($websiteId)) {
            if ($shouldDelete) {
                $this->queueDeletion($user->getEmail(), $websiteId);
            } else {
                $this->unsubscribeCustomer($user->getEmail(), $websiteId);
            }
        }

        return $this;
    }

    /**
     * @param string $email
     * @param int $websiteId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function unsubscribeCustomer(string $email, int $websiteId)
    {
        /** @var Contact $contact */
        $contact = $this->contactFactory->create()
            ->loadByCustomerEmail($email, $websiteId);

        $contact->setSubscriberStatus(null)
            ->setLastSubscribedAt(null)
            ->setIsSubscriber(null);

        $this->contactResource->save($contact);

        $this->importerFactory->create()->registerQueue(
            Importer::IMPORT_TYPE_SUBSCRIBER_UPDATE,
            ['email' => $email, 'id' => $contact->getId()],
            Importer::MODE_SUBSCRIBER_UPDATE,
            $websiteId
        );
    }

    /**
     * Queue a contact deletion
     *
     * @param string $email
     * @param int $websiteId
     */
    private function queueDeletion(string $email, int $websiteId)
    {
        try {
            $this->importerFactory->create()->registerQueue(
                Importer::IMPORT_TYPE_CONTACT,
                $email,
                Importer::MODE_CONTACT_DELETE,
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
}
