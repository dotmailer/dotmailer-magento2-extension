<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\SubscriptionDataFactory;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\Subscriber as DotdigitalSubscriber;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var SubscriptionDataFactory
     */
    private $subscriptionDataFactory;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param Contact $contactResource
     * @param ImporterFactory $importerFactory
     * @param ContactFactory $contactFactory
     * @param Data $data
     * @param StoreManagerInterface $storeManagerInterface
     * @param PublisherInterface $publisher
     * @param SubscriptionDataFactory $subscriptionDataFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        PublisherInterface $publisher,
        SubscriptionDataFactory $subscriptionDataFactory
    ) {
        $this->contactFactory = $contactFactory;
        $this->importerFactory = $importerFactory;
        $this->helper = $data;
        $this->contactResource = $contactResource;
        $this->storeManager = $storeManagerInterface;
        $this->publisher = $publisher;
        $this->subscriptionDataFactory = $subscriptionDataFactory;
    }

    /**
     * Execute.
     *
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
     * Unsubscribe customer
     *
     * @param string $email
     * @param int $websiteId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function unsubscribeCustomer(string $email, int $websiteId)
    {
        try {
            $contact = $this->contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);

            $contact->setSubscriberStatus(null)
                ->setLastSubscribedAt(null)
                ->setIsSubscriber(0);

            $this->contactResource->save($contact);

            $unsubscriber = $this->subscriptionDataFactory->create();
            $unsubscriber->setEmail($email);
            $unsubscriber->setId($contact->getId());
            $unsubscriber->setWebsiteId($websiteId);
            $unsubscriber->setType('unsubscribe');

            $this->publisher->publish(DotdigitalSubscriber::TOPIC_NEWSLETTER_SUBSCRIPTION, $unsubscriber);
        } catch (\Exception $e) {
            $this->helper->debug('Error when unsubscribing a customer', [(string) $e]);
        }
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
            $this->helper->debug('Error when deleting a contact', [(string) $e]);
        }
    }
}
