<?php

namespace Dotdigitalgroup\Email\Observer\Newsletter;

use Magento\Framework\Event\Observer;
use Dotdigitalgroup\Email\Model\Consent\ConsentManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NewsletterSubscriberSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    private $configHelper;

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
     * @var ConsentManager
     */
    private $consentManager;

    /**
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param ConsentManager $consentManager
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        ConsentManager $consentManager
    ) {
        $this->helper = $data;
        $this->configHelper = $this->helper->configHelperFactory->create();
        $this->storeManager   = $storeManagerInterface;
        $this->contactFactory = $contactFactory;
        $this->consentManager = $consentManager;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            $subscriber = $observer->getEvent()->getSubscriber();
            $email = $subscriber->getEmail();
            $subscriberStatus = $subscriber->getSubscriberStatus();
            $websiteId = $this->storeManager->getStore($storeId = $subscriber->getStoreId())
                ->getWebsiteId();

            //If not confirmed or not enabled.
            if ($subscriberStatus == \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED ||
                !$this->helper->isEnabled($websiteId) ||
                !$this->configHelper->isConsentSubscriberEnabled($websiteId)
            ) {
                return $this;
            }

            $contactEmail = $this->contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);
            $emailContactId = $contactEmail->getId();
            $this->consentManager->createConsentRecord($emailContactId, $storeId);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
