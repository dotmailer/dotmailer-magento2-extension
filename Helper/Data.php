<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Helper\Config as EmailConfig;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * General most used helper to work with config data, saving updating and generating.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DM_FIELD_LIMIT = 1000;

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
     * @var \Magento\Framework\App\Config\Storage\Writer
     */
    public $writer;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory
     */
    public $clientFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\ConfigFactory ConfigFactory
     */
    public $configHelperFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $datetime;

    /**
     * @var SerializerInterface
     */
    public $serializer;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    public $contactResource;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    private $quoteResource;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\User\Model\ResourceModel\User
     */
    private $userResource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

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
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\ResourceConnection $adapter
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\Store $store
     * @param \Magento\Framework\App\Config\Storage\Writer $writer
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory
     * @param ConfigFactory $configHelperFactory
     * @param SerializerInterface $serializer
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\User\Model\ResourceModel\User $userResource
     * @param Logger $logger
     * @param RequestInterface $request
     * @param EncryptorInterface $encryptor
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $adapter,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\Store $store,
        \Magento\Framework\App\Config\Storage\Writer $writer,
        \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory,
        \Dotdigitalgroup\Email\Helper\ConfigFactory $configHelperFactory,
        SerializerInterface $serializer,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\User\Model\ResourceModel\User $userResource,
        Logger $logger,
        RequestInterface $request,
        EncryptorInterface $encryptor,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->serializer = $serializer;
        $this->adapter = $adapter;
        $this->productMetadata = $productMetadata;
        $this->contactFactory = $contactFactory;
        $this->resourceConfig = $resourceConfig;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->store = $store;
        $this->writer = $writer;
        $this->clientFactory = $clientFactory;
        $this->configHelperFactory = $configHelperFactory;
        $this->datetime = $dateTime;
        $this->timezone = $timezone;
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->quoteResource = $quoteResource;
        $this->quoteFactory = $quoteFactory;
        $this->userResource = $userResource;
        $this->contactResource = $contactResource;
        $this->logger = $logger;
        $this->request = $request;
        $this->encryptor = $encryptor;
        $this->reinitableConfig = $reinitableConfig;

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
     * @param string $apiSpaceId
     * @param string $token
     * @param $website
     * @return $this
     */
    public function saveChatApiSpaceIdAndToken(string $apiSpaceId, string $token, $website)
    {
        $scopeInterface = $website->getId() ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        $this->resourceConfig->saveConfig(
            EmailConfig::XML_PATH_LIVECHAT_API_SPACE_ID,
            $apiSpaceId,
            $scopeInterface,
            $website->getId()
        );
        $this->resourceConfig->saveConfig(
            EmailConfig::XML_PATH_LIVECHAT_API_TOKEN,
            $this->encryptor->encrypt($token),
            $scopeInterface,
            $website->getId()
        );
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
     * Get api credentials enabled.
     *
     * @param int $website
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabled($website = 0)
    {
        $website = $this->storeManager->getWebsite($website);
        $enabled = $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
        );
        $apiUsername = $this->getApiUsername($website);
        $apiPassword = $this->getApiPassword($website);
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
     * Disable wishlist sync.
     *
     * @param string $scope
     * @param int $scopeId
     */
    public function disableTransactionalDataConfig($scope, $scopeId)
    {
        $this->resourceConfig->saveConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
            0,
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
     * Is the page tracking enabled.
     *
     * @return bool
     */
    public function isPageTrackingEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED
        );
    }

    /**
     * Is the Roi page tracking enabled.
     *
     * @return bool
     */
    public function isRoiTrackingEnabled()
    {
        return (bool)$this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED
        );
    }

    /**
     * @return bool
     */
    public function isWebBehaviourTrackingEnabled()
    {
        return (bool)$this->scopeConfig->isSetFlag(Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID);
    }

    /**
     * Store name datafield.
     *
     * @param \Magento\Store\Model\Website $website
     *
     * @return boolean|string
     */
    public function getMappedStoreName(\Magento\Store\Model\Website $website)
    {
        $mapped = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME
        );
        $storeName = ($mapped) ? $mapped : '';

        return $storeName;
    }

    /**
     * Get the contact id for the customer based on website id.
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return bool|string
     */
    public function getContactId($email, $websiteId)
    {
        if (!$this->isEnabled($websiteId)) {
            return false;
        }

        $contactFromTable = $this->getContactByEmail($email, $websiteId);
        if ($contactId = $contactFromTable->getContactId()) {
            return $contactId;
        }

        $contact = $this->getOrCreateContact($email, $websiteId, $contactFromTable);
        if ($contact && isset($contact->id)) {
            return $contact->id;
        }

        return false;
    }

    /**
     * @param string $email
     * @param int $websiteId
     * @param boolean $contactFromTable
     *
     * @return bool|object
     */
    public function getOrCreateContact($email, $websiteId, $contactFromTable = false)
    {
        if (!$this->isEnabled($websiteId)) {
            return false;
        }

        if ($contactFromTable) {
            $contact = $contactFromTable;
        } else {
            $contact = $this->contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);
        }

        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->getContactByEmail($email);
        if (!isset($response->id)) {
            $response = $client->postContacts($email);
        }

        if (isset($response->status) && $response->status !== 'Subscribed') {
            $contact->setEmailImported(1);
            $contact->setSuppressed(1);
            $this->saveContact($contact);
            return false;
        }

        //save contact id
        if (isset($response->id)) {
            $contact->setContactId($response->id);
            $this->saveContact($contact);
        } else {
            //curl operation timeout
            return false;
        }

        return $response;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param string $intervalSpec
     * @return array
     */
    public function getSuppressedContacts($website, $intervalSpec = 'PT24H')
    {
        $limit = 5;
        $maxToSelect = 1000;
        $skip = $i = 0;
        $contacts = [];
        $suppressedEmails = [];
        $date = $this->timezone->date()->sub($this->dateIntervalFactory->create(['interval_spec' => $intervalSpec]));
        $dateString = $date->format(\DateTime::W3C);
        $client = $this->getWebsiteApiClient($website);

        //there is a maximum of request we need to loop to get more suppressed contacts
        for ($i = 0; $i <= $limit; $i++) {
            $apiContacts = $client->getContactsSuppressedSinceDate($dateString, $maxToSelect, $skip);

            // skip no more contacts or the api request failed
            if (empty($apiContacts) || isset($apiContacts->message)) {
                break;
            }
            foreach ($apiContacts as $apiContact) {
                $contacts[] = $apiContact;
            }
            $skip += 1000;
        }

        // Contacts to un-subscribe
        foreach ($contacts as $apiContact) {
            if (isset($apiContact->suppressedContact)) {
                $suppressedContactEmail = $apiContact->suppressedContact->email;
                if (!array_key_exists($suppressedContactEmail, $suppressedEmails)) {
                    $suppressedEmails[$suppressedContactEmail] = [
                        'email' => $apiContact->suppressedContact->email,
                        'removed_at' => $apiContact->dateRemoved,
                    ];
                }
            }
        }

        return array_values($suppressedEmails);
    }

    /**
     * Api client by website.
     *
     * @param Magento\Store\Model\Website|int $website
     * @param string $username
     * @param string $password
     *
     * @return \Dotdigitalgroup\Email\Model\Apiconnector\Client
     */
    public function getWebsiteApiClient($website = 0, $username = '', $password = '')
    {
        if ($username && $password) {
            $apiUsername = $username;
            $apiPassword = $password;
        } else {
            $apiUsername = $this->getApiUsername($website);
            $apiPassword = $this->getApiPassword($website);
        }

        $client = $this->clientFactory->create();
        $client->setApiUsername($apiUsername)
            ->setApiPassword($apiPassword);

        $websiteId = $this->storeManager->getWebsite($website)->getId();
        //Get api endpoint
        $apiEndpoint = $this->getApiEndpoint($websiteId, $client);

        //Set api endpoint on client
        if ($apiEndpoint) {
            $client->setApiEndpoint($apiEndpoint);
        }

        return $client;
    }

    /**
     * Get Api endPoint
     *
     * @param int $websiteId
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     *
     * @return string|
     */
    public function getApiEndpoint($websiteId, $client)
    {
        //Get from DB
        $apiEndpoint = $this->getApiEndPointFromConfig($websiteId);

        //Nothing from DB then fetch from api
        if (!$apiEndpoint) {
            $apiEndpoint = $this->getApiEndPointFromApi($client);
            //Save it in DB
            if ($apiEndpoint) {
                $this->saveApiEndpoint($apiEndpoint, $websiteId);
            }
        }
        return $apiEndpoint;
    }

    /**
     * Get api end point from api
     *
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     *
     * @return string|boolean
     */
    public function getApiEndPointFromApi($client)
    {
        $accountInfo = $client->getAccountInfo();
        $apiEndpoint = false;
        if (is_object($accountInfo) && !isset($accountInfo->message)) {
            //save endpoint for account
            foreach ($accountInfo->properties as $property) {
                if ($property->name == 'ApiEndpoint' && !empty($property->value)) {
                    $apiEndpoint = $property->value;
                    break;
                }
            }
        }
        return $apiEndpoint;
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
     * Save api endpoint into config.
     *
     * @param string $apiEndpoint
     * @param int $websiteId
     *
     * @return null
     */
    public function saveApiEndpoint($apiEndpoint, $websiteId)
    {
        if ($websiteId > 0) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
        } else {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
        $this->writer->save(
            \Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT,
            $apiEndpoint,
            $scope,
            $websiteId
        );
    }

    /**
     * @param int $website
     *
     * @return string|boolean
     */
    public function getApiUsername($website = 0)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_USERNAME,
            $website
        );
    }

    /**
     * @param int $website
     *
     * @return string|boolean
     */
    public function getApiPassword($website = 0)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_PASSWORD,
            $website
        );
    }

    /**
     * Get the address book for customer.
     *
     * @param int $website
     *
     * @return string
     */
    public function getCustomerAddressBook($website = 0)
    {
        $website = $this->storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
        );
    }

    /**
     * Subscriber address book.
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface|int $website
     *
     * @return string|boolean
     */
    public function getSubscriberAddressBook($website)
    {
        $website = $this->storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );
    }

    /**
     * Guest address book.
     *
     * @param \Magento\Store\Api\Data\WebsiteInterface|int $website
     *
     * @return string|boolean
     */
    public function getGuestAddressBook($website)
    {
        $website = $this->storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getid()
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
     * Get custom datafield mapped.
     *
     * @param int $website
     *
     * @return array|mixed
     */
    public function getCustomAttributes($website = 0)
    {
        $attr = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );

        if (!$attr) {
            return [];
        }

        try {
            return $this->serializer->unserialize($attr);
        } catch (\InvalidArgumentException $e) {
            $this->logger->debug((string) $e);
            return [];
        }
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
     * Order status config value.
     *
     * @param int $website
     *
     * @return array|bool
     */
    public function getConfigSelectedStatus($website = 0)
    {
        $status = $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS,
            $website
        );
        if ($status) {
            return explode(',', $status);
        } else {
            return false;
        }
    }

    /**
     * Get website config.
     *
     * @param string $path
     * @param int $website
     * @param string $scope
     *
     * @return string|boolean
     */
    public function getWebsiteConfig($path, $website = 0, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $website
        );
    }

    /**
     * Get array of custom attributes for orders from config.
     *
     * @param int $website
     *
     * @return array|bool
     */
    public function getConfigSelectedCustomOrderAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES,
            $website
        );
        if ($customAttributes) {
            return explode(',', $customAttributes);
        } else {
            return false;
        }
    }

    /**
     * Mark contact for reimport.
     *
     * @param int $customerId
     *
     * @return null
     */
    public function setConnectorContactToReImport($customerId)
    {
        $contactModel = $this->contactFactory->create();
        $contactModel->loadByCustomerId($customerId)
            ->setEmailImported(
                \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED
            );
        $this->contactResource->save($contactModel);
    }

    /**
     * Disable website config when the request is made admin area only!
     *
     * @param string $path
     *
     * @return null
     */
    public function disableConfigForWebsite($path)
    {
        $scopeId = 0;
        if ($website = $this->request->getParam('website')) {
            $scope = 'websites';
            $scopeId = $this->storeManager->getWebsite($website)->getId();
        } else {
            $scope = 'default';
        }
        $this->resourceConfig->saveConfig(
            $path,
            0,
            $scope,
            $scopeId
        );
    }

    /**
     * Number of customers with duplicate emails, emails as total number.
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    public function getCustomersWithDuplicateEmails()
    {
        $customers = $this->customerFactory->create()
            ->getCollection();

        //duplicate emails
        $customers->getSelect()
            ->columns(['emails' => 'COUNT(e.entity_id)'])
            ->group('email')
            ->having('emails > ?', 1);
        return $customers;
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
     * Update data fields.
     *
     * @param string $email
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param string $storeName
     *
     * @return null
     */
    public function updateDataFields($email, $website, $storeName)
    {
        $data = [];
        if ($storeNameKey = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
        )
        ) {
            $data[] = [
                'Key' => $storeNameKey,
                'Value' => $storeName,
            ];
        }
        if ($websiteName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME
        )
        ) {
            $data[] = [
                'Key' => $websiteName,
                'Value' => $website->getName(),
            ];
        }
        if (!empty($data)) {
            //update data fields
            if ($this->isEnabled($website)) {
                $client = $this->getWebsiteApiClient($website);
                $client->updateContactDatafieldsByEmail($email, $data);
            }
        }
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
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
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
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Customer sync enabled.
     *
     * @param int $website
     *
     * @return bool
     */
    public function isCustomerSyncEnabled($website = 0)
    {
        $website = $this->storeManager->getWebsite($website);

        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
        );
    }

    /**
     * Customer sync size limit.
     *
     * @param int $website
     *
     * @return string|boolean
     */
    public function getSyncLimit($website = 0)
    {
        $website = $this->storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
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
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
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
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $automationCampaignId;
    }

    /**
     * Api- update the product name most expensive.
     *
     * @param string $name
     * @param string $email
     * @param int $websiteId
     *
     * @return null
     */
    public function updateAbandonedProductName($name, $email, $websiteId)
    {
        if ($this->isEnabled($websiteId)) {
            $client = $this->getWebsiteApiClient($websiteId);
            // id config data mapped
            $field = $this->getAbandonedProductName();

            if ($field) {
                $data[] = [
                    'Key' => $field,
                    'Value' => $name,
                ];
                //update data field for contact
                $client->updateContactDatafieldsByEmail($email, $data);
            }
        }
    }

    /**
     * Get mapped product name.
     *
     * @return boolean|string
     */
    public function getAbandonedProductName()
    {
        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_PRODUCT_NAME
        );
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
     * Product review from config to link the product link.
     *
     * @param int $website
     *
     * @return boolean|string
     */
    public function getReviewReminderAnchor($website)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_AUTOMATION_REVIEW_ANCHOR,
            $website
        );
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
     * Get review anchor value.
     *
     * @param int $website
     *
     * @return string
     */
    public function getAnchor($website)
    {
        return $this->getReviewWebsiteSettings(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_ANCHOR, $website);
    }

    /**
     * Get review display type.
     *
     * @param int $website
     *
     * @return string
     */
    public function getDisplayType($website)
    {
        return $this->getReviewWebsiteSettings(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_DISPLAY_TYPE,
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
     * Get website datafields for subscriber
     *
     * @param \Magento\Store\Model\Website $website
     * @return array
     */
    public function getWebsiteSalesDataFields($website)
    {
        $subscriberDataFields = [
            'website_name' => '',
            'store_name' => '',
            'subscriber_status' => '',
            'number_of_orders' => '',
            'average_order_value' => '',
            'total_spend' => '',
            'last_order_date' => '',
            'last_increment_id' => '',
            'most_pur_category' => '',
            'most_pur_brand' => '',
            'most_freq_pur_day' => '',
            'most_freq_pur_mon' => '',
            'first_category_pur' => '',
            'last_category_pur' => '',
            'first_brand_pur' => '',
            'last_brand_pur' => ''
        ];

        $store = $website->getDefaultStore();
        $mappedData = $this->scopeConfig->getValue(
            'connector_data_mapping/customer_data',
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        $mappedData = array_intersect_key($mappedData, $subscriberDataFields);
        foreach ($mappedData as $key => $value) {
            if (!$value) {
                unset($mappedData[$key]);
            }
        }
        return $mappedData;
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

    /**
     * @param int $quoteId
     * @return array
     */
    public function getQuoteAllItemsFor($quoteId)
    {
        $quoteModel = $this->quoteFactory->create();
        $this->quoteResource->load($quoteModel, $quoteId);
        $quoteItems = $quoteModel->getAllItems();

        return $quoteItems;
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
     * @return string
     */
    public function getRegionPrefix()
    {
        $websiteId = $this->getWebsite()->getId();
        $apiEndpoint = $this->getApiEndPointFromConfig($websiteId);

        if (empty($apiEndpoint)) {
            return '';
        }

        preg_match("/https:\/\/(.*)api.dotmailer.com/", $apiEndpoint, $matches);
        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * @return string
     */
    public function getPageTrackingUrl()
    {
        $version = $this->getTrackingScriptVersionNumber();
        return '//' . $this->getRegionPrefix() . 't.trackedlink.net/_dmpt'
            . ($version ? '.js?v=' . $version : '');
    }

    /**
     * @return string
     */
    public function getPageTrackingUrlForSuccessPage()
    {
        $version = $this->getTrackingScriptVersionNumber();
        return '//' . $this->getRegionPrefix() . 't.trackedlink.net/_dmmpt'
            . ($version ? '.js?v=' . $version : '');
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
     * @param int $websiteId
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
    private function getTrackingScriptVersionNumber()
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
