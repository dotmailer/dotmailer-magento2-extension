<?php

namespace Dotdigitalgroup\Email\Observer\Newsletter;

use Dotdigitalgroup\Email\Model\Newsletter\Subscriber;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;

/**
 * Contact newsletter subscription change.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ChangeContactSubscription implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
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
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var bool
     */
    private $isSubscriberNew;

    /**
     * ChangeContactSubscription constructor.
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param Automation $automationResource
     * @param Automation\CollectionFactory $automationCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        Automation $automationResource,
        Automation\CollectionFactory $automationCollectionFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime
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
        $this->dateTime = $dateTime;
    }

    /**
     * Change contact subscription status.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
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
            if ($subscriberStatus == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                //Set contact as subscribed
                $contactEmail->setSubscriberImported(0)
                    ->setIsSubscriber('1');

                //Subscriber subscribed when it is suppressed in table then re-subscribe
                if ($contactEmail->getSuppressed()) {
                    $this->importerFactory->create()->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
                        ['email' => $email],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
                        $websiteId
                    );
                    //Set to subscriber imported and reset the subscriber as suppressed
                    $contactEmail->setSubscriberImported(1)
                        ->setSuppressed(null);
                }
                //save contact
                $this->contactResource->save($contactEmail);

                //not subscribed
            } else {
                //skip if contact is suppressed
                if ($contactEmail->getSuppressed()) {
                    return $this;
                }
                $contactEmail->setSubscriberImported(1)
                    ->setIsSubscriber(null);
                //save contact
                $this->contactResource->save($contactEmail);

                //need to confirm enabled, to keep before the subscription data for contentinsight.
                if ($subscriberStatus == \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED ||
                    $subscriberStatus == \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE) {
                    return $this;
                }

                //Add subscriber update to importer queue
                $this->importerFactory->create()->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBER_UPDATE,
                    ['email' => $email, 'id' => $contactEmail->getId()],
                    \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_UPDATE,
                    $websiteId
                );
            }

            // fix for a multiple hit of the observer. stop adding the duplicates on the automation
            $emailReg = $this->registry->registry($email . '_subscriber_save');
            if ($emailReg) {
                return $this;
            }
            $this->registry->unregister($email . '_subscriber_save'); // additional measure
            $this->registry->register($email . '_subscriber_save', $email);
            //add subscriber to automation
            $this->addSubscriberToAutomation($email, $subscriber, $websiteId);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }

    /**
     * Register subscriber to automation.
     *
     * @param string $email
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param string|int|null $websiteId
     *
     * @return void
     */
    private function addSubscriberToAutomation($email, $subscriber, $websiteId)
    {
        $programId = $this->helper->getWebsiteConfig(
            'connector_automation/visitor_automation/subscriber_automation',
            $websiteId
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
        if (($isSubscriberConfirming && $this->hasSubscriberAutomation($email)) ||
            (!$isSubscriberConfirming && !$this->isSubscriberNew)
        ) {
            return;
        }

        $store = $this->storeManager->getStore($subscriber->getStoreId());

        //save subscriber to the queue
        $automation = $this->automationFactory->create()
            ->setEmail($email)
            ->setAutomationType(
                \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER
            )
            ->setEnrolmentStatus(
                \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING
            )
            ->setTypeId($subscriber->getId())
            ->setWebsiteId($websiteId)
            ->setStoreName($store->getName())
            ->setProgramId($programId);
        $this->automationResource->save($automation);
    }

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     *
     * @return bool
     */
    private function isSubscriberConfirming($subscriber)
    {
        $subscriberStatusNow = $subscriber->getSubscriberStatus();
        $subscriberStatusBefore = $subscriber->getOrigData('subscriber_status');

        return $subscriberStatusNow == 1 && $subscriberStatusBefore == 2;
    }

    /**
     * Check if a subscriber_automation has already been processed for an email address.
     *
     * @param string $email
     *
     * @return bool
     */
    private function hasSubscriberAutomation($email)
    {
        $matching = $this->automationCollectionFactory->create()
            ->getSubscriberAutomationByEmail($email);

        return $matching->getSize() ? true : false;
    }
}
