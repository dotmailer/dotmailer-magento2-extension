<?php

namespace Dotdigitalgroup\Email\ViewModel\Customer\Account;

use Dotdigitalgroup\Email\Model\Consent;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Model\Session;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;

class Newsletter implements ArgumentInterface
{
    /**
     * @var Consent
     */
    private $consent;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Consent $consent
     * @param Session $customerSession
     * @param SubscriberFactory $subscriberFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Consent $consent,
        Session $customerSession,
        SubscriberFactory $subscriberFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->consent = $consent;
        $this->customerSession = $customerSession;
        $this->subscriberFactory = $subscriberFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get customer consent text.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCustomerConsentText(): string
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        return $this->consent->getConsentCustomerText($websiteId) ?: '';
    }

    /**
     * Is subscribed.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isSubscribed()
    {
        $subscriber = $this->subscriberFactory->create()
            ->loadByCustomer(
                $this->customerSession->getCustomerId(),
                $this->storeManager->getWebsite()->getId()
            );
        if ($subscriber->getId()) {
            return $subscriber->isSubscribed();
        }

        return false;
    }
}
