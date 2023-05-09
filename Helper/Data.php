<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Helper\Config as EmailConfig;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\Apiconnector\Account;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * General most used helper to work with config data, saving updating and generating.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const DM_FIELD_LIMIT = 1000;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    public $resourceConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    public $contactFactory;

    /**
     * @var ContactResponseHandler
     */
    private $contactResponseHandler;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    public $productMetadata;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $adapter;

    /**
     * @var \Magento\Store\Model\Store
     */
    public $store;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory
     */
    private $clientFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\ConfigFactory ConfigFactory
     */
    public $configHelperFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $datetime;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    public $contactResource;

    /**
     * @var \Magento\User\Model\ResourceModel\User
     */
    private $userResource;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Account
     */
    private $account;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param ContactResponseHandler $contactResponseHandler
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\ResourceConnection $adapter
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\Store $store
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory
     * @param ConfigFactory $configHelperFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\User\Model\ResourceModel\User $userResource
     * @param Logger $logger
     * @param RequestInterface $request
     * @param EncryptorInterface $encryptor
     * @param Account $account
     */
    public function __construct(
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        ContactResponseHandler $contactResponseHandler,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $adapter,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\Store $store,
        \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory,
        \Dotdigitalgroup\Email\Helper\ConfigFactory $configHelperFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\User\Model\ResourceModel\User $userResource,
        Logger $logger,
        RequestInterface $request,
        EncryptorInterface $encryptor,
        Account $account
    ) {
        $this->adapter = $adapter;
        $this->productMetadata = $productMetadata;
        $this->contactFactory = $contactFactory;
        $this->resourceConfig = $resourceConfig;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->store = $store;
        $this->clientFactory = $clientFactory;
        $this->configHelperFactory = $configHelperFactory;
        $this->datetime = $dateTime;
        $this->userResource = $userResource;
        $this->contactResource = $contactResource;
        $this->contactResponseHandler = $contactResponseHandler;
        $this->logger = $logger;
        $this->request = $request;
        $this->encryptor = $encryptor;
        $this->account = $account;

        parent::__construct($context);
    }

    /**
     * Save API credentials sent by microsite
     *
     * @param string $apiUsername
     * @param string $apiPassword
     * @param string|null $apiEndpoint
     * @param $website
     * @return $this
     */
    public function saveApiCredentials(string $apiUsername, string $apiPassword, string $apiEndpoint = null, $website)
    {
        $scopeInterface = $website->getId() ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        $this->resourceConfig->saveConfig(
            EmailConfig::XML_PATH_CONNECTOR_API_USERNAME,
            $apiUsername,
            $scopeInterface,
            $website->getId()
        );
        $this->resourceConfig->saveConfig(
            EmailConfig::XML_PATH_CONNECTOR_API_PASSWORD,
            $this->encryptor->encrypt($apiPassword),
            $scopeInterface,
            $website->getId()
        );
        if ($apiEndpoint) {
            $this->resourceConfig->saveConfig(
                EmailConfig::PATH_FOR_API_ENDPOINT,
                $apiEndpoint,
                $scopeInterface,
                $website->getId()
            );
        }
        return $this;
    }

    /**
     * Enable Engagement Cloud integration
     *
     * @return $this
     */
    public function enableEngagementCloud($website)
    {
        $scopeInterface = $website->getId() ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        $this->resourceConfig->saveConfig(
            EmailConfig::XML_PATH_CONNECTOR_API_ENABLED,
            true,
            $scopeInterface,
            $website->getId()
        );
        return $this;
    }

    /**
     * Get api credentials enabled.
     *
     * @param int $websiteId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabled($websiteId = 0)
    {
        $enabled = $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        $apiUsername = $this->getApiUsername($websiteId);
        $apiPassword = $this->getApiPassword($websiteId);
        if (!$apiUsername || !$apiPassword || !$enabled) {
            return false;
        }

        return true;
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    public function isStoreEnabled($storeId)
    {
        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get all stores.
     *
     * @param bool|false $default
     *
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    public function getStores($default = false)
    {
        return $this->storeManager->getStores($default);
    }

    /**
     * Passcode for dynamic content links.
     *
     * @param string $authRequest
     *
     * @return bool
     */
    public function auth($authRequest)
    {
        if ($authRequest != $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE
        )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check for IP address to match the ones from config.
     *
     * @return bool
     */
    public function isIpAllowed()
    {
        if ($ipString = $this->getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_IP_RESTRICTION_ADDRESSES,
            'default'
        )
        ) {
            //string to array
            $ipArray = explode(',', $ipString);

            //remove white spaces
            foreach ($ipArray as $key => $ip) {
                $ipArray[$key] = trim($ip);
            }

            //ip address
            $ipAddress = $this->_remoteAddress->getRemoteAddress();

            if (in_array($ipAddress, $ipArray)) {
                return true;
            }
        } else {
            //empty ip list from configuration will ignore the validation
            return true;
        }

        $this->log(sprintf("Failed to authenticate IP address - %s", $ipAddress));

        return false;
    }

    /**
     * Get config scope value.
     *
     * @param string $path
     * @param string $contextScope
     * @param null $contextScopeId
     *
     * @return int|float|string|boolean
     */
    public function getConfigValue(
        $path,
        $contextScope = 'default',
        $contextScopeId = null
    ) {
        $config = $this->scopeConfig->getValue(
            $path,
            $contextScope,
            $contextScopeId
        );

        return $config;
    }

    /**
     * Customer id datafield.
     *
     * @return string
     */
    public function getMappedCustomerId()
    {
        return $this->getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID,
            'default'
        );
    }

    /**
     * Order id datafield.
     *
     * @return string
     */
    public function getMappedOrderId()
    {
        return $this->getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID,
            'default'
        );
    }

    /**
     * Get website selected in admin.
     * @return \Magento\Store\Api\Data\WebsiteInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getWebsite()
    {
        $websiteId = $this->request->getParam('website', false);
        if ($websiteId) {
            return $this->storeManager->getWebsite($websiteId);
        }

        return $this->storeManager->getWebsite();
    }

    /**
     * @param $websiteId
     * @return \Magento\Store\Api\Data\WebsiteInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getWebsiteById($websiteId)
    {
        return $this->storeManager->getWebsite($websiteId);
    }

    /**
     * Get website for selected scope in admin
     *
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function getWebsiteForSelectedScopeInAdmin()
    {
        /**
         * See first if store param exist. If it does than get website from store.
         * If website param does not exist then default value returned 0 "default scope"
         * This is because there is no website param in default scope
         */
        $storeId = $this->request->getParam('store');
        $websiteId = ($storeId) ? $this->storeManager->getStore($storeId)->getWebsiteId() :
            $this->request->getParam('website', 0);
        return $this->storeManager->getWebsite($websiteId);
    }

    /**
     * Get passcode from config.
     *
     * @return string
     */
    public function getPasscode()
    {
        $websiteId = (int)$this->request->getParam('website', false);

        $scope = 'default';
        $scopeId = '0';
        if ($websiteId) {
            $scope = 'website';
            $scopeId = $websiteId;
        }

        $passcode = $this->getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE,
            $scope,
            $scopeId
        );

        return $passcode;
    }

    /**
     * Save config data.
     *
     * @deprecated Inject WriterInterface in your classes and call save directly.
     * @see \Magento\Framework\App\Config\Storage\WriterInterface
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     */
    public function saveConfigData($path, $value, $scope, $scopeId)
    {
        $this->resourceConfig->saveConfig(
            $path,
            $value,
            $scope,
            $scopeId
        );
    }

    /**
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     */
    public function deleteConfigData($path, $scope, $scopeId)
    {
        $this->resourceConfig->deleteConfig(
            $path,
            $scope,
            $scopeId
        );
    }

    /**
     * Last order id datafield.
     *
     * @return string
     */
    public function getLastOrderId()
    {
        return $this->getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID,
            'default'
        );
    }

    /**
     * Log data to the extension's log file.
     * INFO (200): Interesting events.
     *
     * @param string $data
     * @param array $extra
     *
     * @return null
     */
    public function log($data, $extra = [])
    {
        $this->logger->info($data, $extra);
    }

    /**
     * Log data to the extension's log file.
     * DEBUG (100): Detailed debug information.
     *
     * @param string $message
     * @param array $extra
     *
     * @return null
     */
    public function debug($message, $extra = [])
    {
        $this->logger->debug($message, $extra);
    }

    /**
     * Log data to the extension's log file.
     * ERROR (400): Runtime errors.
     *
     * @param string $message
     * @param array $extra
     */
    public function error($message, $extra = [])
    {
        $this->logger->error($message, $extra);
    }

    /**
     * Get if the log is enabled for connector.
     *
     * @return bool
     */
    public function isDebugEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED
        );
    }

    /**
     * @param string|int $websiteId
     * @return bool
     */
    public function isPageTrackingEnabled($websiteId)
    {
        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param string|int $websiteId
     * @return bool
     */
    public function isRoiTrackingEnabled($websiteId)
    {
        return (bool)$this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param string|int $websiteId
     * @return bool
     */
    public function isWebBehaviourTrackingEnabled($websiteId)
    {
        return (bool)$this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @deprecated Use client methods directly.
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return bool|\stdClass
     */
    public function getOrCreateContact($email, $websiteId)
    {
        if (!$this->isEnabled($websiteId)) {
            return false;
        }

        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->getContactByEmail($email);
        if (!isset($response->id)) {
            $response = $client->postContacts($email);
        }

        return $this->contactResponseHandler->updateContactFromResponse($response, $email, $websiteId);
    }

    /**
     * Api client by website.
     *
     * @param int $websiteId
     * @param string $username
     * @param string $password
     *
     * @return \Dotdigitalgroup\Email\Model\Apiconnector\Client
     */
    public function getWebsiteApiClient(int $websiteId = 0, $username = '', $password = '')
    {
        if ($username && $password) {
            $apiUsername = $username;
            $apiPassword = $password;
        } else {
            $apiUsername = $this->getApiUsername($websiteId);
            $apiPassword = $this->getApiPassword($websiteId);
        }

        $client = $this->clientFactory->create();
        $client->setApiUsername($apiUsername)
            ->setApiPassword($apiPassword);

        $apiEndpoint = $this->getApiEndPointFromConfig($websiteId);

        if ($apiEndpoint) {
            $client->setApiEndpoint($apiEndpoint);
        }

        return $client;
    }

    /**
     * Get api end point for given website
     *
     * @param int $websiteId
     *
     * @return string|boolean
     */
    public function getApiEndPointFromConfig($websiteId)
    {
        if ($websiteId > 0) {
            $apiEndpoint = $this->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT,
                $websiteId
            );
        } else {
            $apiEndpoint = $this->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT,
                $websiteId,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
        return $apiEndpoint;
    }

    /**
     * @param int $websiteId
     *
     * @return string|boolean
     */
    public function getApiUsername($websiteId = 0)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_USERNAME,
            $websiteId
        );
    }

    /**
     * @param int $websiteId
     *
     * @return string|boolean
     */
    public function getApiPassword($websiteId = 0)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_PASSWORD,
            $websiteId
        );
    }

    /**
     * Get the address book for customer.
     *
     * @param int $websiteId
     *
     * @return string
     */
    public function getCustomerAddressBook($websiteId)
    {
        return $this->scopeConfig->getValue(
            EmailConfig::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get subscriber address book.
     *
     * @param int $websiteId
     *
     * @return string|boolean
     */
    public function getSubscriberAddressBook($websiteId)
    {
        return $this->scopeConfig->getValue(
            EmailConfig::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Guest address book.
     *
     * @param string|int $websiteId
     * @return string|boolean
     */
    public function getGuestAddressBook($websiteId)
    {
        return $this->scopeConfig->getValue(
            EmailConfig::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get all websites.
     *
     * @param bool|false $default
     *
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    public function getWebsites($default = false)
    {
        return $this->storeManager->getWebsites($default);
    }

    /**
     * Get callback authorization link.
     *
     * @return string
     */
    public function getRedirectUri()
    {
        $callback = $this->configHelperFactory->create()
            ->getCallbackUrl();

        return $callback;
    }

    /**
     * Get website config.
     *
     * @param string $path
     * @param int $websiteId
     * @param string $scope
     *
     * @return string|boolean
     */
    public function getWebsiteConfig($path, $websiteId = 0, $scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $websiteId
        );
    }

    /**
     * Generate the baseurl for the default store
     * dynamic content will be displayed.
     *
     * @return string
     */
    public function generateDynamicUrl()
    {
        $website = $this->request->getParam('website', false);

        //set website url for the default store id
        $website = ($website) ? $this->storeManager->getWebsite($website) : 0;

        $defaultGroup = $this->storeManager->getWebsite($website)
            ->getDefaultGroup();

        if (!$defaultGroup) {
            return $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB
            );
        }

        //base url
        $baseUrl = $this->storeManager->getStore(
            $defaultGroup->getDefaultStore()
        )->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);

        return $baseUrl;
    }

    /**
     * get sales_flat_order table description.
     *
     * @return array
     */
    public function getOrderTableDescription()
    {
        $salesTable = $this->adapter->getTableName('sales_order');
        $adapter = $this->adapter->getConnection('sales');
        $columns = $adapter->describeTable($salesTable);

        return $columns;
    }

    /**
     * Get feefo logon config value.
     *
     * @return string
     */
    public function getFeefoLogon()
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_LOGON
        );
    }

    /**
     * Get feefo reviews limit config value.
     *
     * @return string
     */
    public function getFeefoReviewsPerProduct()
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_REVIEWS
        );
    }

    /**
     * Get feefo logo template config value.
     *
     * @return string
     */
    public function getFeefoLogoTemplate()
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_TEMPLATE
        );
    }

    /**
     * Update last quote id datafield.
     *
     * @param int $quoteId
     * @param string $email
     * @param int $websiteId
     *
     * @return null
     */
    public function updateLastQuoteId($quoteId, $email, $websiteId)
    {
        if ($this->isEnabled($websiteId)) {
            $client = $this->getWebsiteApiClient($websiteId);
            //last quote id config data mapped
            $quoteIdField = $this->getLastQuoteId();

            $data[] = [
                'Key' => $quoteIdField,
                'Value' => $quoteId,
            ];
            //update datafields for contact
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }

    /**
     * Get last quote id datafield.
     *
     * @return string
     */
    public function getLastQuoteId()
    {
        return $this->getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID,
            'default'
        );
    }

    /**
     * Get order sync enabled value from configuration.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    public function isOrderSyncEnabled($websiteId = 0)
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get the catalog sync enabled value from config.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    public function isCatalogSyncEnabled($websiteId = 0)
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Customer sync enabled.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    public function isCustomerSyncEnabled($websiteId)
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get the guest sync enabled value.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    public function isGuestSyncEnabled($websiteId = 0)
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Is subscriber sync enabled.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    public function isSubscriberSyncEnabled($websiteId = 0)
    {
        return $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get the config id by the automation type.
     *
     * @param string $automationType
     * @param int $storeId
     *
     * @return string|boolean
     */
    public function getAutomationIdByType($automationType, $storeId = 0)
    {
        $path = constant(EmailConfig::class . '::' . $automationType);

        $automationCampaignId = $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $automationCampaignId;
    }

    /**
     * Trigger log for api calls longer then config value.
     *
     * @param int $websiteId
     *
     * @return boolean|string
     */
    public function getApiResponseTimeLimit($websiteId = 0)
    {
        $website = $this->storeManager->getWebsite($websiteId);
        $limit = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DEBUG_API_REQUEST_LIMIT
        );

        return $limit;
    }

    /**
     * Get display type for review product.
     *
     * @param int $website
     *
     * @return boolean|string
     */
    public function getReviewDisplayType($website)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_REVIEW_DISPLAY_TYPE,
            $website
        );
    }

    /**
     * Get config value on website level.
     *
     * @param string $path
     * @param int $website
     *
     * @return boolean|string
     */
    public function getReviewWebsiteSettings($path, $website)
    {
        return $this->getWebsiteConfig($path, $website);
    }

    /**
     * @param int $website
     *
     * @return string
     */
    public function getOrderStatus($website)
    {
        return $this->getReviewWebsiteSettings(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_STATUS, $website);
    }

    /**
     * Get review setting delay time.
     *
     * @param int $website
     *
     * @return int
     */
    public function getDelay($website)
    {
        return $this->getReviewWebsiteSettings(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_DELAY, $website);
    }

    /**
     * Is the review new product enabled.
     *
     * @param int $website
     *
     * @return bool
     */
    public function isNewProductOnly($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_NEW_PRODUCT,
            $website
        );
    }

    /**
     * Get review campaign for automation review.
     *
     * @param int $website
     *
     * @return int
     */
    public function getCampaign($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_CAMPAIGN,
            $website
        );
    }

    /**
     * Check if both frontend and backend are secure (HTTPS).
     *
     * @return bool
     */
    public function isFrontendAdminSecure()
    {
        $frontend = $this->store->isFrontUrlSecure();
        $admin = $this->getWebsiteConfig(\Magento\Store\Model\Store::XML_PATH_SECURE_IN_ADMINHTML);
        $current = $this->store->isCurrentlySecure();

        if ($frontend && $admin && $current) {
            return true;
        }

        return false;
    }

    /**
     * Get the abandoned cart limit.
     *
     * @return boolean|string
     */
    public function getAbandonedCartLimit()
    {
        $cartLimit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT
        );

        return $cartLimit;
    }

    /**
     * @param string $cronJob
     * @return boolean|string
     */
    public function getDateLastCronRun($cronJob)
    {
        return $this->contactResource->getDateLastCronRun($cronJob);
    }

    /**
     * Validate date range
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return bool|string
     */
    public function validateDateRange($dateFrom, $dateTo)
    {
        if (!$this->validateDate($dateFrom) || !$this->validateDate($dateTo)) {
            return 'From date or to date is not valid.';
        }
        if (strtotime($dateFrom) > strtotime($dateTo)) {
            return 'To date cannot be earlier than from date.';
        }
        return false;
    }

    /**
     * @param string $date
     * @return bool|\DateTime|false
     */
    public function validateDate($date)
    {
        try {
            return date_create($date);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get difference between dates
     *
     * @param string $created
     * @return false|int
     */
    public function getDateDifference($created)
    {
        $now = $this->datetime->gmtDate();

        return strtotime($now) - strtotime($created);
    }

    /**
     * Validate code
     *
     * @param string $code
     * @return bool
     */
    public function isCodeValid($code)
    {
        $codeFromConfig = $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE,
            $this->getWebsite()
        );

        return $codeFromConfig === $code;
    }

    /**
     * @param \Magento\User\Model\User $adminUser
     * @param string $token
     *
     * @return null
     */
    public function setRefreshTokenForUser($adminUser, $token)
    {
        $adminUser = $adminUser->setRefreshToken($token);
        $this->userResource->save($adminUser);
    }

    /** Get brand attribute selected from config by website id
     *
     * @param int $websiteId
     * @return string|boolean
     */
    public function getBrandAttributeByWebsiteId($websiteId)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_BRAND_ATTRIBUTE,
            $websiteId
        );
    }

    /**
     * Can show additional books?
     *
     * @param \Magento\Store\Model\Website|int $website
     * @return string|boolean
     */
    public function getCanShowAdditionalSubscriptions($website)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_CHANGE_BOOKS,
            $website
        );
    }

    /**
     * Can show data fields?
     *
     * @param \Magento\Store\Model\Website|int $website
     * @return boolean|string
     */
    public function getCanShowDataFields($website)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_CAN_SHOW_FIELDS,
            $website
        );
    }

    /**
     * Address book ids to display
     *
     * @param \Magento\Store\Model\Website $website
     * @return array
     */
    public function getAddressBookIdsToShow($website)
    {
        $bookIds = $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADDRESSBOOK_PREF_SHOW_BOOKS,
            $website
        );

        if (empty($bookIds)) {
            return [];
        }

        $additionalFromConfig = explode(',', $bookIds);
        //unset the default option - for multi select
        if ($additionalFromConfig[0] == '0') {
            unset($additionalFromConfig[0]);
        }

        return $additionalFromConfig;
    }

    /**
     * Get region prefix.
     *
     * Will return a string like 'r1-'.
     *
     * @return string
     */
    public function getRegionPrefix()
    {
        $websiteId = $this->getWebsite()->getId();
        $apiEndpoint = $this->getApiEndPointFromConfig($websiteId);

        if (empty($apiEndpoint)) {
            return '';
        }

        return $this->account->getRegionPrefix($apiEndpoint);
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    public function isOnlySubscribersForAC($storeId)
    {
        $value = $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_ALLOW_NON_SUBSCRIBERS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return ($value) ? false : true;
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function isOnlySubscribersForReview($websiteId)
    {
        $value = $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_ALLOW_NON_SUBSCRIBERS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        return ($value) ? false : true;
    }

    /**
     * Check if allow non-subscribers is enabled.
     *
     * @param string|int $websiteId
     *
     * @return bool
     */
    public function isOnlySubscribersForContactSync($websiteId)
    {
        $value = $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ALLOW_NON_SUBSCRIBERS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        return ($value) ? false : true;
    }

    /**
     * @deprecated Load directly via dependency injection.
     *
     * @return ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    /**
     * @param $email
     * @param $websiteId
     *
     * @return \Dotdigitalgroup\Email\Model\Contact
     */
    public function getContactByEmail($email, $websiteId)
    {
        $contact = $this->contactFactory->create()
            ->loadByCustomerEmail($email, $websiteId);
        return $contact;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Contact $contact
     */
    public function saveContact($contact)
    {
        $this->contactResource->save($contact);
    }

    /**
     * @param int $websiteId
     * @return bool|string
     */
    public function getProfileId($websiteId = 0)
    {
        return $this->getWebsiteConfig(CONFIG::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID, $websiteId);
    }

    /**
     * Get the version number to append to _dmpt tracking script
     *
     * @return int|null
     */
    public function getTrackingScriptVersionNumber()
    {
        return (int)$this->scopeConfig->getValue(Config::XML_PATH_TRACKING_SCRIPT_VERSION);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isConnectorEnabledAtAnyLevel()
    {
        foreach ($this->storeManager->getWebsites(true) as $website) {
            if ($this->isEnabled($website->getId())) {
                return true;
            }
        }

        return false;
    }
}
