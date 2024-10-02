<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Consent\ConsentManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;

class Consent extends AbstractModel
{
    public const CONSENT_TEXT_LIMIT = '1000';

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * @var array
     */
    private $consentText = [];

    /**
     * @var array
     */
    private $customerConsentText = [];

    /**
     * Constructor.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Consent::class);
    }

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Config $config
     * @param StringUtils $stringUtils
     * @param DateTimeFactory $dateTimeFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $config,
        StringUtils $stringUtils,
        DateTimeFactory $dateTimeFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configHelper = $config;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->stringUtils = $stringUtils;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Get consent datetime.
     *
     * Overloads the magic method to return a date string prepared for the V3 API schema
     * - i.e. ISO 8601, UTC timestamp.
     *
     * @return string
     */
    public function getConsentDateTime()
    {
        return $this->dateTimeFactory->create(
            $this->getData('consent_datetime'),
            new \DateTimeZone('UTC')
        )->format(\DateTimeInterface::ATOM);
    }

    /**
     * Get consent text.
     *
     * @param string $consentUrl
     * @param int $websiteId
     *
     * @deprecated We fetch consent text per store.
     * @see ConsentManager::getConsentTextForStoreView()
     * @return string
     */
    private function getConsentTextForWebsite(string $consentUrl, $websiteId): string
    {
        if (! isset($this->consentText[$websiteId])) {
            $this->consentText[$websiteId] = $this->getConsentSubscriberText($websiteId);
        }
        $consentText = $this->consentText[$websiteId];

        if (! isset($this->customerConsentText[$websiteId])) {
            $this->customerConsentText[$websiteId] = $this->getConsentCustomerText($websiteId);
        }
        $customerConsentText = $this->customerConsentText[$websiteId];

        //customer checkout and registration if consent text not empty
        if (strlen($customerConsentText) && $this->isLinkMatchCustomerRegistrationOrCheckout($consentUrl)) {
            $consentText = $customerConsentText;
        }

        return $consentText;
    }

    /**
     * Fetch consent customer text.
     *
     * @deprecated Consent text has store scope.
     * @see ConsentManager::getConsentCustomerTextForStore
     *
     * @param int $websiteId
     * @return string
     */
    public function getConsentCustomerText($websiteId): string
    {
        return $this->limitLength(
            $this->configHelper->getWebsiteConfig(Config::XML_PATH_CONSENT_CUSTOMER_TEXT, $websiteId)
        );
    }

    /**
     * Check if consent URL matches checkout or customer account paths.
     *
     * @deprecated Moved in to ConsentManager class.
     * @see ConsentManager::isLinkMatchCustomerRegistrationOrCheckout()
     *
     * @param string $consentUrl
     * @return bool
     */
    private function isLinkMatchCustomerRegistrationOrCheckout(string $consentUrl): bool
    {
        return (strpos($consentUrl, 'checkout/') !== false ||
            strpos($consentUrl, 'connector/customer/index/') !== false ||
            strpos($consentUrl, 'customer/account/') !== false);
    }

    /**
     * Fetch consent subscriber text.
     *
     * @deprecated Consent text has store scope.
     * @see ConsentManager::getConsentSubscriberTextForStore
     *
     * @param string|int $websiteId
     * @return string
     */
    private function getConsentSubscriberText($websiteId): string
    {
        return $this->limitLength(
            $this->configHelper->getWebsiteConfig(Config::XML_PATH_CONSENT_SUBSCRIBER_TEXT, $websiteId)
        );
    }

    /**
     * Fetch length limit.
     *
     * @deprecated Moved in to ConsentManager class.
     * @see ConsentManager::limitLength()
     *
     * @param string|null $value
     * @return string
     */
    private function limitLength(?string $value): string
    {
        if ($this->stringUtils->strlen($value) > self::CONSENT_TEXT_LIMIT) {
            $value = $this->stringUtils->substr($value, 0, self::CONSENT_TEXT_LIMIT);
        }

        return (string) $value;
    }
}
