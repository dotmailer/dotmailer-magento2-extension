<?php

namespace Dotdigitalgroup\Email\Model\Consent;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\ConsentFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Consent as ConsentResource;
use Dotdigitalgroup\Email\Model\Consent;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\HTTP\Header;
use Magento\Framework\App\Response\RedirectInterface;

class ConsentManager
{
    public const CONSENT_TEXT_LIMIT = '1000';

    /**
     * @var Http
     */
    private $http;

    /**
     * @var Header
     */
    private $header;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var ConsentFactory
     */
    private $consentFactory;

    /**
     * @var ConsentResource
     */
    private $consentResource;

    /**
     * @var Consent
     */
    private $consent;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * @param Http $http
     * @param Header $header
     * @param RedirectInterface $redirect
     * @param ConsentFactory $consentFactory
     * @param ConsentResource $consentResource
     * @param Consent $consent
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param StringUtils $stringUtils
     */
    public function __construct(
        Http $http,
        Header $header,
        RedirectInterface $redirect,
        ConsentFactory $consentFactory,
        ConsentResource $consentResource,
        Consent $consent,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        StringUtils $stringUtils
    ) {
        $this->http = $http;
        $this->header = $header;
        $this->redirect = $redirect;
        $this->consentFactory = $consentFactory;
        $this->consentResource = $consentResource;
        $this->consent = $consent;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->stringUtils = $stringUtils;
    }

    /**
     * Create consent record
     *
     * @param string|int $emailContactId
     * @param string|int $storeId
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createConsentRecord($emailContactId, $storeId): void
    {
        $consentModel = $this->consentFactory->create();

        $consentIp = $this->getClientIp();
        $consentUrl = $this->getRefererUrl();
        $consentUserAgent = $this->getHttpUserAgent();

        $consentText = $this->getConsentTextForStoreView(
            $consentUrl,
            $storeId
        );

        $consentModel->setEmailContactId($emailContactId)
            ->setConsentUrl($consentUrl)
            ->setConsentDatetime(time())
            ->setConsentIp($consentIp)
            ->setConsentText($consentText)
            ->setConsentUserAgent($consentUserAgent);

        $this->consentResource->save($consentModel);
    }

    /**
     * Get consent text.
     *
     * @param string $consentUrl
     * @param string|int $storeId
     * @return string
     */
    public function getConsentTextForStoreView(string $consentUrl, $storeId): string
    {
        $consentText = $this->getConsentSubscriberTextForStore($storeId);
        $customerConsentText = $this->getConsentCustomerTextForStore($storeId);

        //customer checkout and registration if consent text not empty
        if (strlen($customerConsentText) && $this->isLinkMatchCustomerRegistrationOrCheckout($consentUrl)) {
            $consentText = $customerConsentText;
        }

        return $consentText;
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
     * Is consent enabled.
     *
     * @param string|int $storeId
     *
     * @return bool
     */
    public function isConsentEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONSENT_EMAIL_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $storeId
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
            strpos($consentUrl, 'connector/customer/index/') !== false ||
            strpos($consentUrl, 'customer/account/') !== false);
    }

    /**
     * Get client ip.
     *
     * @return string
     */
    private function getClientIp()
    {
        return $this->http->getClientIp();
    }

    /**
     * Get referer url.
     *
     * @return string
     */
    private function getRefererUrl()
    {
        return $this->redirect->getRefererUrl();
    }

    /**
     * Get http user agent.
     *
     * @return string
     */
    private function getHttpUserAgent()
    {
        return $this->header->getHttpUserAgent();
    }

    /**
     * Fetch length limit.
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
