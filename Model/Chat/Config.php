<?php

namespace Dotdigitalgroup\Email\Model\Chat;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Checkout\Model\SessionFactory;
use Dotdigitalgroup\Email\Helper\Config as EmailConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Website;
use Magento\Framework\UrlInterface;

/**
 * Class Config
 * Before use this class you need to call setScopeAndWebsiteId(int $websiteId)
 * in order to enable scope view and store data.
 * Set to Default scope by default
 */
class Config
{
    const XML_PATH_LIVECHAT_ENABLED = 'chat_api_credentials/settings/enabled';
    const XML_PATH_LIVECHAT_API_SPACE_ID = 'chat_api_credentials/credentials/api_space_id';
    const XML_PATH_LIVECHAT_API_TOKEN = 'chat_api_credentials/credentials/api_token';

    const CHAT_PORTAL_URL = 'WebChat';
    const CHAT_CONFIGURE_TEAM_PATH = 'team/users/all';
    const CHAT_CONFIGURE_WIDGET_PATH = 'account/chat-settings';

    const MAGENTO_ROUTE = 'connector/email/accountcallback';
    const MAGENTO_PROFILE_CALLBACK_ROUTE = 'connector/chat/profile?isAjax=true';

    /**
     * Cookie used to get chat profile ID
     */
    const COOKIE_CHAT_PROFILE = 'ddg_chat_profile_id';

    /**
     * Paths which should have their values encrypted
     */
    const ENCRYPTED_CONFIG_PATHS = [
        self::XML_PATH_LIVECHAT_API_TOKEN,
        EmailConfig::XML_PATH_CONNECTOR_API_PASSWORD,
    ];

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var SessionFactory
     */
    private $sessionFactory;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $portalUrl;

    /**
     * @var string
     */
    private $scopeInterface = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

    /**
     * @var int
     */
    private $websiteId = 0;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var EmailConfig
     */
    private $emailConfig;

    /**
     * Config constructor
     *
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param ReinitableConfigInterface $reinitableConfig
     * @param WriterInterface $configWriter
     * @param SessionFactory $sessionFactory
     * @param UrlInterface $urlBuilder
     * @param EmailConfig $emailConfig
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig,
        ReinitableConfigInterface $reinitableConfig,
        WriterInterface $configWriter,
        SessionFactory $sessionFactory,
        UrlInterface $urlBuilder,
        EmailConfig $emailConfig
    ) {
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->reinitableConfig = $reinitableConfig;
        $this->configWriter = $configWriter;
        $this->sessionFactory = $sessionFactory;
        $this->urlBuilder = $urlBuilder;
        $this->emailConfig = $emailConfig;
    }

    /**
     * Sets the Scope level
     * @param Website|null
     */
    public function setScopeAndWebsiteId($website)
    {
        $this->scopeInterface = $website->getId() ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->websiteId = $website->getId();
    }

    /**
     * @return mixed
     */
    public function getApiSpaceId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LIVECHAT_API_SPACE_ID, $this->scopeInterface, (string) $this->websiteId);
    }
    /**
     *
     * @return string|null
     */
    public function getApiToken()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_LIVECHAT_API_TOKEN, $this->scopeInterface, (string) $this->websiteId);
        return $this->encryptor->decrypt($value);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getChatPortalUrl()
    {
        return $this->emailConfig->getRegionAwarePortalUrl() . self::CHAT_PORTAL_URL;
    }

    /**
     * @return string
     */
    public function getConfigureChatTeamUrl()
    {
        return $this->emailConfig->getRegionAwarePortalUrl() . self::CHAT_CONFIGURE_TEAM_PATH;
    }

    /**
     * @return string
     */
    public function getConfigureChatWidgetUrl()
    {
        return $this->emailConfig->getRegionAwarePortalUrl() . self::CHAT_CONFIGURE_WIDGET_PATH;
    }

    /**
     * @return string
     */
    public function getConfigureChatTeamButtonUrl()
    {
        return $this->urlBuilder->getUrl('dotdigitalgroup_email/chat/team');
    }

    /**
     * @return string
     */
    public function getConfigureChatWidgetButtonUrl()
    {
        return $this->urlBuilder->getUrl('dotdigitalgroup_email/chat/widget');
    }

    /**
     * Save API credentials sent by microsite
     *
     * @param string $apiUsername
     * @param string $apiPassword
     * @param string|null $apiEndpoint
     * @return $this
     */
    public function saveApiCredentials(string $apiUsername, string $apiPassword, string $apiEndpoint = null)
    {
        $this->configWriter->save(EmailConfig::XML_PATH_CONNECTOR_API_USERNAME, $apiUsername, $this->scopeInterface, $this->websiteId);
        $this->configWriter->save(EmailConfig::XML_PATH_CONNECTOR_API_PASSWORD, $this->encryptor->encrypt($apiPassword), $this->scopeInterface, $this->websiteId);
        if ($apiEndpoint) {
            $this->configWriter->save(EmailConfig::PATH_FOR_API_ENDPOINT, $apiEndpoint, $this->scopeInterface, $this->websiteId);
        }
        return $this;
    }

    /**
     * Save chat API space ID and token
     *
     * @param string $apiSpaceId
     * @param string $token
     * @return $this
     */
    public function saveChatApiSpaceIdAndToken(string $apiSpaceId, string $token)
    {
        $this->configWriter->save(self::XML_PATH_LIVECHAT_API_SPACE_ID, $apiSpaceId, $this->scopeInterface, $this->websiteId);
        $this->configWriter->save(self::XML_PATH_LIVECHAT_API_TOKEN, $this->encryptor->encrypt($token), $this->scopeInterface, $this->websiteId);
        return $this;
    }

    /**
     * Reinitialise config object
     *
     * @return $this
     */
    public function reinitialiseConfig()
    {
        $this->reinitableConfig->reinit();
        return $this;
    }

    /**
     * Enable Engagement Cloud integration
     *
     * @return $this
     */
    public function enableEngagementCloud()
    {
        $this->configWriter->save(EmailConfig::XML_PATH_CONNECTOR_API_ENABLED, true, $this->scopeInterface, $this->websiteId);
        return $this;
    }

    /**
     * Enable or disable live chat
     *
     * @param $value
     * @return $this
     */
    public function setLiveChatStatus($value)
    {
        $this->configWriter->save(self::XML_PATH_LIVECHAT_ENABLED, $value, $this->scopeInterface, $this->websiteId);
        return $this;
    }

    /**
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session ?: $this->session = $this->sessionFactory->create();
    }

    /**
     * Determines if Chat is enabled or not
     *
     * @return bool
     */
    public function isChatEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_LIVECHAT_ENABLED, $this->scopeInterface, $this->websiteId);
    }

    /**
     * Deletes only Api Space Id and Token
     */
    public function deleteChatApiCredentials()
    {
        if ($this->getApiSpaceId()) {
            $this->configWriter->delete(self::XML_PATH_LIVECHAT_API_SPACE_ID, $this->scopeInterface, $this->websiteId);
            $this->configWriter->delete(self::XML_PATH_LIVECHAT_API_TOKEN, $this->scopeInterface, $this->websiteId);
        }
    }
}
