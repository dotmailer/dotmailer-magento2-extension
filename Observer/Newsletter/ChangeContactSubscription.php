<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\Newsletter;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Contact as ContactModel;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Subscriber as DotdigitalSubscriber;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Email\Model\Queue\Data\SubscriptionDataFactory;

/**
 * Contact newsletter subscription change.
 */
class ChangeContactSubscription implements ObserverInterface
{
    /**
     * @var Contact
     */
    private $contactResource;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * @var Automation
     */
    private $automationResource;

    /**
     * @var Automation\CollectionFactory
     */
    private $automationCollectionFactory;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var AutomationPublisher
     */
    private $automationPublisher;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var bool
     */
    private $isSubscriberNew;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var SubscriptionDataFactory
     */
    private $subscriptionDataFactory;

    /**
     * ChangeContactSubscription constructor.
     *
     * @param AutomationFactory $automationFactory
     * @param Automation $automationResource
     * @param CollectionFactory $automationCollectionFactory
     * @param ContactFactory $contactFactory
     * @param Contact $contactResource
     * @param Registry $registry
     * @param Data $data
     * @param StoreManagerInterface $storeManagerInterface
     * @param ImporterFactory $importerFactory
     * @param AutomationPublisher $automationPublisher
     * @param DateTime $dateTime
     * @param ScopeConfigInterface $scopeConfig
     * @param PublisherInterface $publisher
     * @param SubscriptionDataFactory $subscriptionDataFactory
     */
    public function __construct(
        AutomationFactory $automationFactory,
        Automation $automationResource,
        Automation\CollectionFactory $automationCollectionFactory,
        ContactFactory $contactFactory,
        Contact $contactResource,
        Registry $registry,
        Data $data,
        StoreManagerInterface $storeManagerInterface,
        ImporterFactory $importerFactory,
        AutomationPublisher $automationPublisher,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        PublisherInterface $publisher,
        SubscriptionDataFactory $subscriptionDataFactory
    ) {
        $this->contactResource = $contactResource;
        $this->automationFactory = $automationFactory;
        $this->automationResource = $automationResource;
        $this->automationCollectionFactory = $automationCollectionFactory;
        $this->contactFactory = $contactFactory;
        $this->helper = $data;
        $this->storeManager = $storeManagerInterface;
        $this->registry = $registry;
        $this->importerFactory = $importerFactory;
        $this->automationPublisher = $automationPublisher;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->publisher = $publisher;
        $this->subscriptionDataFactory = $subscriptionDataFactory;
    }

    /**
     * Change contact subscription status.
     *
     * @param Observer $observer
     *
     * @return $this
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $this->isSubscriberNew = $subscriber->isObjectNew();
        $email = $subscriber->getEmail();
        $storeId = $subscriber->getStoreId();
        $subscriberStatus = $subscriber->getSubscriberStatus();
        $websiteId = $this->storeManager->getStore($subscriber->getStoreId())
            ->getWebsiteId();

        //api is enabled
        if (!$this->helper->isEnabled($websiteId)) {
            return $this;
        }

        try {
            $contactEmail = $this->contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);

            //update the contact
            $contactEmail->setStoreId($storeId)
                ->setSubscriberStatus($subscriberStatus)
                ->setLastSubscribedAt(
                    (int) $subscriberStatus === Subscriber::STATUS_SUBSCRIBED
                        ? $this->dateTime->formatDate(true)
                        : $contactEmail->getLastSubscribedAt()
                );

            // only for subscribers
            if ($subscriberStatus == Subscriber::STATUS_SUBSCRIBED) {
                $contactEmail->setSubscriberImported(1)
                    ->setIsSubscriber(1);

                if ($contactEmail->getSuppressed()) {
                    $contactEmail->setSuppressed(null);
                    $this->contactResource->save($contactEmail);

                    $resubscribeData = $this->subscriptionDataFactory->create();
                    $resubscribeData->setId($contactEmail->getId());
                    $resubscribeData->setEmail($email);
                    $resubscribeData->setWebsiteId($websiteId);
                    $resubscribeData->setType('resubscribe');
                    $this->publisher->publish(DotdigitalSubscriber::TOPIC_NEWSLETTER_SUBSCRIPTION, $resubscribeData);
                } else {
                    // save first in order to have a row id for the queue publish
                    $this->contactResource->save($contactEmail);

                    $subscribeData = $this->subscriptionDataFactory->create();
                    $subscribeData->setId($contactEmail->getId());
                    $subscribeData->setEmail($email);
                    $subscribeData->setWebsiteId($websiteId);
                    $subscribeData->setType('subscribe');
                    $this->publisher->publish(DotdigitalSubscriber::TOPIC_NEWSLETTER_SUBSCRIPTION, $subscribeData);
                }
            //not subscribed
            } else {
                if ($contactEmail->getSuppressed()) {
                    return $this;
                }

                if ($subscriberStatus == Subscriber::STATUS_UNCONFIRMED ||
                    $subscriberStatus == Subscriber::STATUS_NOT_ACTIVE) {
                    $this->saveContactAsNotSubscribed($contactEmail);
                    return $this;
                }

                //Check if previously subscribed
                if ($contactEmail->getIsSubscriber()) {
                    $unsubscriber = $this->subscriptionDataFactory->create();
                    $unsubscriber->setId($contactEmail->getId());
                    $unsubscriber->setEmail($email);
                    $unsubscriber->setWebsiteId($websiteId);
                    $unsubscriber->setType('unsubscribe');

                    $this->publisher->publish(DotdigitalSubscriber::TOPIC_NEWSLETTER_SUBSCRIPTION, $unsubscriber);
                }

                $this->saveContactAsNotSubscribed($contactEmail);
            }

            // fix for a multiple hit of the observer. stop adding the duplicates on the automation
            $emailReg = $this->registry->registry($email . '_subscriber_save');
            if ($emailReg) {
                return $this;
            }
            $this->registry->unregister($email . '_subscriber_save'); // additional measure
            $this->registry->register($email . '_subscriber_save', $email);
            //add subscriber to automation
            $this->addSubscriberToAutomation($email, $subscriber, $websiteId, $storeId);
        } catch (Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }

    /**
     * Register subscriber to automation.
     *
     * @param string $email
     * @param Subscriber $subscriber
     * @param string|int $websiteId
     * @param string|int $storeId
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    private function addSubscriberToAutomation($email, $subscriber, $websiteId, $storeId)
    {
        $programId = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // If no New Subscriber automation is set, ignore
        if (!$programId) {
            return;
        }

        $isSubscriberConfirming = $this->isSubscriberConfirming($subscriber);

        /* Do not create a New Subscriber automation if:
         * 1. The subscriber is confirming their subscription for Need to Confirm, and hasn't been processed already OR
         * 2. The subscriber is not confirming - and they aren't new
         */
        if (($isSubscriberConfirming && $this->hasSubscriberAutomation($email, $websiteId)) ||
            (!$isSubscriberConfirming && !$this->isSubscriberNew)
        ) {
            return;
        }

        //save subscriber to the queue
        $automation = $this->automationFactory->create()
            ->setEmail($email)
            ->setAutomationType(AutomationTypeHandler::AUTOMATION_TYPE_NEW_SUBSCRIBER)
            ->setEnrolmentStatus(StatusInterface::PENDING)
            ->setTypeId($subscriber->getId())
            ->setWebsiteId($websiteId)
            ->setStoreId($storeId)
            ->setStoreName($this->storeManager->getStore($storeId)->getName())
            ->setProgramId($programId);
        $this->automationResource->save($automation);

        $this->automationPublisher->publish($automation);
    }

    /**
     * Returns true if current and previous statuses indicate a subscriber is confirming their subscription.
     *
     * @param Subscriber $subscriber
     *
     * @return bool
     */
    private function isSubscriberConfirming($subscriber)
    {
        $subscriberStatusNow = $subscriber->getSubscriberStatus();
        $subscriberStatusBefore = $subscriber->getOrigData('subscriber_status');

        $expectedStatusNow = $subscriberStatusNow == Subscriber::STATUS_SUBSCRIBED;
        $expectedStatusBefore = $subscriberStatusBefore != Subscriber::STATUS_SUBSCRIBED;

        return $expectedStatusNow && $expectedStatusBefore;
    }

    /**
     * Check if a subscriber_automation has already been processed for an email address
     *
     * @param string $email
     * @param string|int $websiteId
     * @return bool
     */
    private function hasSubscriberAutomation($email, $websiteId)
    {
        $matching = $this->automationCollectionFactory->create()
            ->getSubscriberAutomationByEmail($email, $websiteId);

        return $matching->getSize() ? true : false;
    }

    /**
     * Save contact as unsubscribed.
     *
     * @param ContactModel $contact
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function saveContactAsNotSubscribed(ContactModel $contact)
    {
        $contact->setIsSubscriber(0);
        $this->contactResource->save($contact);
    }
}
