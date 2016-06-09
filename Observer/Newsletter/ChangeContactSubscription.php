<?php

namespace Dotdigitalgroup\Email\Observer\Newsletter;

class ChangeContactSubscription implements \Magento\Framework\Event\ObserverInterface
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    protected $_automationFactory;
    /**
     * @var
     */
    protected $_importerFactory;

    /**
     * ChangeContactSubscription constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory    $contactFactory
     * @param \Magento\Framework\Registry                    $registry
     * @param \Dotdigitalgroup\Email\Helper\Data             $data
     * @param \Magento\Store\Model\StoreManagerInterface     $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
    ) {
        $this->_automationFactory = $automationFactory;
        $this->_contactFactory = $contactFactory;
        $this->_helper = $data;
        $this->_storeManager = $storeManagerInterface;
        $this->_registry = $registry;
        $this->_importerFactory = $importerFactory->create();
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
        $email = $subscriber->getEmail();
        $storeId = $subscriber->getStoreId();
        $subscriberStatus = $subscriber->getSubscriberStatus();
        $websiteId = $this->_storeManager->getStore($subscriber->getStoreId())
            ->getWebsiteId();
        //check if enabled
        if (!$this->_helper->isEnabled($websiteId)) {
            return $this;
        }

        try {
            $contactEmail = $this->_contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);

            // only for subscribers
            if ($subscriberStatus == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                //Set contact as subscribed
                $contactEmail->setSubscriberStatus($subscriberStatus)
                    ->setIsSubscriber('1');
                //Subscriber subscribed when it is suppressed in table then re-subscribe
                if ($contactEmail->getSuppressed()) {
                    $this->_importerFactory->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
                        ['email' => $email],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
                        $websiteId
                    );
                    //Set to subscriber imported and reset the subscriber as suppressed
                    $contactEmail->setSubscriberImported(1)
                        ->setSuppressed(null);
                }
                //not subscribed
            } else {
                //skip if contact is suppressed
                if ($contactEmail->getSuppressed()) {
                    return $this;
                }
                //update contact id for the subscriber
                $contactId = $contactEmail->getContactId();
                //get the contact id
                if (!$contactId) {
                    $this->_importerFactory->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBER_UPDATE,
                        ['email' => $email, 'id' => $contactEmail->getId()],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_UPDATE,
                        $websiteId
                    );
                }
                $contactEmail->setIsSubscriber(null)
                    ->setSubscriberStatus(\Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED);
            }

            // fix for a multiple hit of the observer. stop adding the duplicates on the automation
            $emailReg = $this->_registry->registry($email . '_subscriber_save');
            if ($emailReg) {
                return $this;
            }
            $this->_registry->register($email . '_subscriber_save', $email);
            //add subscriber to automation
            $this->_addSubscriberToAutomation($email, $subscriber, $websiteId);

            //update the contact
            $contactEmail->setStoreId($storeId);

            //update contact
            $contactEmail->save();
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, []);
        }

        return $this;
    }

    /**
     * Register subscriber to automation.
     *
     * @param $email
     * @param $subscriber
     * @param $websiteId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _addSubscriberToAutomation($email, $subscriber, $websiteId)
    {
        $storeId = $subscriber->getStoreId();
        $store = $this->_storeManager->getStore($storeId);
        $programId = $this->_helper->getWebsiteConfig(
            'connector_automation/visitor_automation/subscriber_automation',
            $websiteId
        );
        //not mapped ignore
        if (!$programId) {
            return;
        }
        try {
            //check the subscriber alredy exists
            $enrolment = $this->_automationFactory->create()
                ->getCollection()
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter(
                    'automation_type',
                    \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER
                )
                ->addFieldToFilter('website_id', $websiteId)
                ->getFirstItem();

            //add new subscriber to automation
            if (!$enrolment->getId()) {
                //save subscriber to the queue
                $automation = $this->_automationFactory->create()
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
                $automation->save();
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }
}
