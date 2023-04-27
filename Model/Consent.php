<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Consent\ConsentManager;
use Dotdigitalgroup\Email\Model\ResourceModel\Consent as ConsentResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Store\Model\ScopeInterface;

class Consent extends AbstractModel
{
    public const CONSENT_TEXT_LIMIT = '1000';

    /**
     * Bulk api import for consent contact fields.
     *
     * @var array
     */
    public static $bulkFields = [
        'consent_text' => 'CONSENTTEXT',
        'consent_url' => 'CONSENTURL',
        'consent_datetime' => 'CONSENTDATETIME',
        'consent_ip' => 'CONSENTIP',
        'consent_user_agent' => 'CONSENTUSERAGENT'
    ];

    /**
     * @var string[]
     */
    public const BULKFIELDTOSINGLEFIELDNAMEMAP = [
        'CONSENTTEXT' => 'TEXT',
        'CONSENTURL' => 'URL',
        'CONSENTDATETIME' => 'DATETIMECONSENTED',
        'CONSENTIP' => 'IPADDRESS',
        'CONSENTUSERAGENT' => 'USERAGENT'
    ];

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ConsentResource
     */
    private $consentResource;

    /**
     * @var CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

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
     * Consent constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Config $config
     * @param ConsentResource $consent
     * @param CollectionFactory $contactCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateTime
     * @param StringUtils $stringUtils
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $config,
        ConsentResource $consent,
        CollectionFactory $contactCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        DateTime $dateTime,
        StringUtils $stringUtils,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime = $dateTime;
        $this->configHelper = $config;
        $this->consentResource = $consent;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->stringUtils = $stringUtils;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
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
