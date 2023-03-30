<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
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
     * Get formatted consent data by contact.
     *
     * @param int $websiteId
     * @param string $email
     *
     * @return array
     */
    public function getFormattedConsentDataByContactForApi($websiteId, $email)
    {
        if (! $this->configHelper->isConsentSubscriberEnabled($websiteId)) {
            return [];
        }

        $this->checkModelLoaded($websiteId, $email);
        $consentText = $this->getConsentText($websiteId);
        $consentDatetime = $this->dateTime->date(\DateTime::ATOM, $this->getConsentDatetime());
        return [
            ['key' => 'TEXT', 'value' => $consentText],
            ['key' => 'URL', 'value' => $this->getConsentUrl()],
            ['key' => 'DATETIMECONSENTED', 'value' => $consentDatetime],
            ['key' => 'IPADDRESS', 'value' => $this->getConsentIp()],
            ['key' => 'USERAGENT', 'value' => $this->getConsentUserAgent()]
        ];
    }

    /**
     * @param string|int $storeId
     *
     * @return bool
     */
	public function isConsentEnabled($storeId)
	{
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONSENT_EMAIL_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
	}

    /**
     * Get consent text.
     *
     * @param string $consentUrl
     * @param int $websiteId
     *
     * @return string
     */
    public function getConsentTextForWebsite(string $consentUrl, $websiteId): string
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
     * @see getConsentCustomerTextForStore
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
     * Fetch consent customer text.
     *
     * @param int $storeId
     * @return string
     */
    public function getConsentCustomerTextForStore($storeId): string
    {
        return $this->limitLength(
            $this->scopeConfig->getValue(
                Config::XML_PATH_CONSENT_CUSTOMER_TEXT,
                ScopeInterface::SCOPE_STORES,
                $storeId
            )
        );
    }

    /**
     * Check if consent URL matches checkout or customer account paths.
     *
     * @param string $consentUrl
     * @return bool
     */
    private function isLinkMatchCustomerRegistrationOrCheckout(string $consentUrl): bool
    {
        return (strpos($consentUrl, 'checkout/') !== false ||
            strpos($consentUrl, 'customer/account/') !== false);
    }

    /**
     * Load consent model.
     *
     * @param int $websiteId
     * @param string $email
     */
    private function checkModelLoaded($websiteId, $email)
    {
        //model not loaded try to load with contact email data
        if (!$this->getId()) {
            //load model using email and website id
            $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail($email, $websiteId);
            if ($contactModel) {
                $this->consentResource->load($this, $contactModel->getEmailContactId(), 'email_contact_id');
            }
        }
    }

    /**
     * Get consent text.
     *
     * @param string|int $websiteId
     * @return string
     */
    private function getConsentText($websiteId): string
    {
        $consentText = $this->getConsentSubscriberText($websiteId);
        $customerConsentText = $this->getConsentCustomerText($websiteId);
        $consentUrl = $this->getConsentUrl();

        //customer checkout and registration if consent text not empty
        if (strlen($customerConsentText) && $consentUrl &&
            $this->isLinkMatchCustomerRegistrationOrCheckout($this->getConsentUrl())
        ) {
            $consentText = $customerConsentText;
        }
        return $consentText;
    }

    /**
     * Fetch consent subscriber text.
     *
     * @deprecated Consent text has store scope.
     * @see getConsentSubscriberTextForStore
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
     * Fetch consent subscriber text.
     *
     * @param string|int $storeId
     * @return string
     */
    private function getConsentSubscriberTextForStore($storeId): string
    {
        return $this->limitLength(
            $this->scopeConfig->getValue(
                Config::XML_PATH_CONSENT_SUBSCRIBER_TEXT,
                ScopeInterface::SCOPE_STORES,
                $storeId
            )
        );
    }

    /**
     * Fetch length limit.
     *
     * @param string|null $value
     * @return string
     */
    private function limitLength($value): string
    {
        if ($this->stringUtils->strlen($value) > self::CONSENT_TEXT_LIMIT) {
            $value = $this->stringUtils->substr($value, 0, self::CONSENT_TEXT_LIMIT);
        }

        return (string) $value;
    }
}
