<?php

namespace Dotdigitalgroup\Email\ViewModel\Customer\Account;

use Dotdigitalgroup\Email\Model\Consent\ConsentManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Model\Session;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;

class Newsletter implements ArgumentInterface
{
    /**
     * @var ConsentManager
     */
    private $consentManager;

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
     * @param ConsentManager $consentManager
     * @param Session $customerSession
     * @param SubscriberFactory $subscriberFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConsentManager $consentManager,
        Session $customerSession,
        SubscriberFactory $subscriberFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->consentManager = $consentManager;
        $this->customerSession = $customerSession;
        $this->subscriberFactory = $subscriberFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get customer consent text.
     *
     * @param string|int|null $storeId
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCustomerConsentText($storeId = null): string
    {
        $storeId = $storeId ?? $this->storeManager->getStore()->getId();
        return $this->consentManager->getConsentCustomerTextForStore($storeId) ?: '';
    }

    /**
     * Can display dd account consent text.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function canDisplayDDAccountConsentText(): bool
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->consentManager->isConsentEnabled($storeId) &&
            strlen($this->getCustomerConsentText($storeId));
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
