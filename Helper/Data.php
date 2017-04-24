<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Helper\Config as EmailConfig;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const MODULE_NAME = 'Dotdigitalgroup_Email';

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
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    public $moduleInterface;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;

    /**
     * @var \Magento\Cron\Model\ScheduleFactory
     */
    public $schelduleFactory;
    /**
     * @var File
     */
    public $fileHelper;
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
     * Data constructor.
     *
     * @param \Magento\Framework\App\ProductMetadata        $productMetadata
     * @param \Dotdigitalgroup\Email\Model\ContactFactory   $contactFactory
     * @param \Magento\Config\Model\ResourceModel\Config    $resourceConfig
     * @param \Magento\Framework\App\ResourceConnection     $adapter
     * @param \Magento\Framework\App\Helper\Context         $context
     * @param \Magento\Store\Model\StoreManagerInterface    $storeManager
     * @param \Magento\Customer\Model\CustomerFactory       $customerFactory
     * @param \Magento\Framework\Module\ModuleListInterface $moduleListInterface
     * @param \Magento\Cron\Model\ScheduleFactory           $schedule
     * @param \Magento\Store\Model\Store                    $store
     * @param \Magento\Framework\App\Config\Storage\Writer $writer
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory
     * @param \Dotdigitalgroup\Email\Helper\ConfigFactory $configHelperFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $adapter,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Module\ModuleListInterface $moduleListInterface,
        \Magento\Cron\Model\ScheduleFactory $schedule,
        \Magento\Store\Model\Store $store,
        \Magento\Framework\App\Config\Storage\Writer $writer,
        \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory,
        \Dotdigitalgroup\Email\Helper\ConfigFactory $configHelperFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {

        $this->adapter          = $adapter;
        $this->schelduleFactory = $schedule;
        $this->productMetadata  = $productMetadata;
        $this->contactFactory   = $contactFactory;
        $this->resourceConfig   = $resourceConfig;
        $this->storeManager     = $storeManager;
        $this->customerFactory  = $customerFactory;
        $this->moduleInterface  = $moduleListInterface;
        $this->store            = $store;
        $this->writer = $writer;
        $this->clientFactory = $clientFactory;
        $this->configHelperFactory = $configHelperFactory;
        $this->datetime = $dateTime;

        parent::__construct($context);
        $this->fileHelper = $fileHelper;
    }

    /**
     * Get api creadentials enabled.
     *
     * @param int $website
     *
     * @return bool
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
        if (! $apiUsername || ! $apiPassword || ! $enabled) {
            return false;
        }

        return true;
    }

    /**
     * @param $storeId
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
     * Passcode for dynamic content liks.
     *
     * @param $authRequest
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
    public function authIpAddress()
    {
        if ($ipString = $this->_getConfigValue(
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

        return false;
    }

    /**
     * Get config scope value.
     *
     * @param        $path
     * @param string $contextScope
     * @param null $contextScopeId
     *
     * @return mixed
     */
    public function _getConfigValue(
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
        return $this->_getConfigValue(
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
        return $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID,
            'default'
        );
    }

    /**
     * Get website selected in admin.
     *
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function getWebsite()
    {
        $websiteId = $this->_request->getParam('website', false);
        if ($websiteId) {
            return $this->storeManager->getWebsite($websiteId);
        }

        return $this->storeManager->getWebsite();
    }

    /**
     * Get passcode from config.
     *
     * @return string
     */
    public function getPasscode()
    {
        $websiteId = $this->_request->getParam('website', false);

        $scope = 'default';
        $scopeId = '0';
        if ($websiteId) {
            $scope = 'website';
            $scopeId = $websiteId;
        }

        $passcode = $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE,
            $scope,
            $scopeId
        );

        return $passcode;
    }

    /**
     * Save config data.
     *
     * @param $path
     * @param $value
     * @param $scope
     * @param $scopeId
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
     * Disable wishlist sync.
     *
     * @param $scope
     * @param $scopeId
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
        return $this->_getConfigValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID,
            'default'
        );
    }

    /**
     * Log data into the connector file.
     * @param $data
     */
    public function log($data)
    {
        $this->fileHelper->info($data);
    }

    /**
     *
     * @param string $message
     * @param mixed $extra
     */
    public function debug($message, $extra)
    {
        $this->fileHelper->debug($message, $extra);
    }

    /**
     *
     * @param $message
     * @param $extra
     */
    public function error($message, $extra)
    {
        $this->debug($message, $extra);
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
     * Store name datafield.
     *
     * @param \Magento\Store\Model\Website $website
     *
     * @return mixed|string
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
     * @param $email string
     * @param $websiteId string
     *
     * @return string
     */
    public function getContactId($email, $websiteId)
    {
        $contact = $this->contactFactory->create()
            ->loadByCustomerEmail($email, $websiteId);
        if ($contactId = $contact->getContactId()) {
            return $contactId;
        }

        if (!$this->isEnabled($websiteId)) {
            return false;
        }

        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->postContacts($email);

        if (isset($response->message)) {
            $contact->setEmailImported(1);
            if ($response->message == \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_CONTACT_SUPPRESSED) {
                $contact->setSuppressed(1);
            }
            $contact->save();
            return false;
        }
        //save contact id
        if (isset($response->id)) {
            $contact->setContactId($response->id)
                ->save();
        } else {
            //curl operation timeout
            return false;
        }

        return $response->id;
    }

    /**
     * Api client by website.
     *
     * @param int $website
     * @param string $username
     * @param string $password
     *
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @param $websiteId
     * @param $client
     * @return mixed
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
     * @return mixed
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
     * @param $websiteId
     * @return mixed
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
     * @param $apiEndpoint
     * @param $websiteId
     */
    public function saveApiEndpoint($apiEndpoint, $websiteId)
    {
        if ($websiteId > 0) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
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
     * @return mixed
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
     * @return mixed
     */
    public function getApiPassword($website = 0)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_PASSWORD,
            $website
        );
    }

    /**
     * Get the addres book for customer.
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
     * @param $website mixed
     *
     * @return mixed / string
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
     * @param $website
     *
     * @return mixed
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
        //@codingStandardsIgnoreStart
        return unserialize($attr);
        //@codingStandardsIgnoreEnd
    }

    /**
     * Get callback authorization link.
     *
     * @return mixed
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
     * @param $path
     * @param int $website
     * @param string $scope
     *
     * @return mixed
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
     * @param $customerId
     */
    public function setConnectorContactToReImport($customerId)
    {
        $contactModel = $this->contactFactory->create();
        $contactModel->loadByCustomerId($customerId)
            ->setEmailImported(
                \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED
            )
            ->save();
    }

    /**
     * Disable website config when the request is made admin area only!
     *
     * @param $path
     */
    public function disableConfigForWebsite($path)
    {
        $scopeId = 0;
        if ($website = $this->_request->getParam('website')) {
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
     * @return mixed
     */
    public function getCustomersWithDuplicateEmails()
    {
        $customers = $this->customerFactory->create()
            ->getCollection();

        //duplicate emails
        //@codingStandardsIgnoreStart
        $customers->getSelect()
            ->columns(['emails' => 'COUNT(e.entity_id)'])
            ->group('email')
            ->having('emails > ?', 1);
        //@codingStandardsIgnoreEnd
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
        $website = $this->_request->getParam('website', false);

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
        $adapter = $this->adapter->getConnection('read');
        $columns = $adapter->describeTable($salesTable);

        return $columns;
    }

    /**
     * Get quote table description.
     *
     * @return array
     */
    public function getQuoteTableDescription()
    {
        $quoteTable = $this->adapter->getTableName('quote');
        $adapter = $this->adapter->getConnection('read');
        $columns = $adapter->describeTable($quoteTable);

        return $columns;
    }

    /**
     * Is email capture enabled.
     *
     * @return bool
     */
    public function isEasyEmailCaptureEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE
        );
    }

    /**
     * Is email capture for newsletter enabled.
     *
     * @return bool
     */
    public function isEasyEmailCaptureForNewsletterEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER
        );
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
     * @param $email
     * @param $website
     * @param $storeName
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
     * @param $quoteId
     * @param $email
     * @param $websiteId
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
            //update datafields for conctact
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
        return $this->_getConfigValue(
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
     * @return mixed
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
     * Get customer datafields mapped - exclude custom attributes.
     *
     * @param $website
     *
     * @return mixed
     */
    public function getWebsiteCustomerMappingDatafields($website)
    {
        //customer mapped data
        $store = $website->getDefaultStore();
        $mappedData = $this->scopeConfig->getValue(
            'connector_data_mapping/customer_data',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getId()
        );
        unset($mappedData['custom_attributes'], $mappedData['abandoned_prod_name']);
        //skip non mapped customer datafields
        foreach ($mappedData as $key => $value) {
            if (!$value) {
                unset($mappedData[$key]);
            }
        }

        return $mappedData;
    }

    /**
     * Get the config id by the automation type.
     *
     * @param string $automationType
     * @param int $storeId
     *
     * @return mixed
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
     * @param $name
     * @param $email
     * @param $websiteId
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
     * @return mixed
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
     * @return mixed
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
     * Check if mailcheck feature is enabled for current store.
     *
     * @return bool
     */
    public function isMailCheckEnabledForCurrentStore()
    {
        $store = $this->storeManager->getStore();

        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_MAILCHECK_ENABLED,
            'store',
            $store
        );
    }

    /**
     * Get url for email capture.
     *
     * @return mixed
     */
    public function getEmailCaptureUrl()
    {
        return $this->storeManager->getStore()->getUrl(
            'connector/ajax/emailcapture',
            ['_secure' => $this->isWebsiteSecure()]
        );
    }

    /**
     * Check if website is secure.
     *
     * @return bool
     */
    public function isWebsiteSecure()
    {
        $isFrontendSecure  = $this->storeManager->getStore()->isFrontUrlSecure();
        $isCurrentlySecure = $this->storeManager->getStore()->isCurrentlySecure();

        if ($isFrontendSecure && $isCurrentlySecure) {
            return true;
        }

        return false;
    }

    /**
     * Product review from config to link the product link.
     *
     * @param int $website
     *
     * @return mixed
     */
    public function getReviewReminderAnchor($website)
    {
        return $this->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_AUTOMATION_REVIEW_ANCHOR,
            $website
        );
    }

    /**
     * Dynamic styles from config.
     *
     * @return array
     */
    public function getDynamicStyles()
    {
        return $dynamicStyle = [
            'nameStyle' => explode(
                ',',
                $this->_getConfigValue(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_STYLE
                )
            ),
            'priceStyle' => explode(
                ',',
                $this->_getConfigValue(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_STYLE
                )
            ),
            'linkStyle' => explode(
                ',',
                $this->_getConfigValue(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_STYLE
                )
            ),
            'otherStyle' => explode(
                ',',
                $this->_getConfigValue(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_STYLE
                )
            ),
            'nameColor' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_COLOR
            ),
            'fontSize' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_FONT_SIZE
            ),
            'priceColor' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_COLOR
            ),
            'priceFontSize' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_FONT_SIZE
            ),
            'urlColor' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_COLOR
            ),
            'urlFontSize' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_FONT_SIZE
            ),
            'otherColor' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_COLOR
            ),
            'otherFontSize' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_FONT_SIZE
            ),
            'docFont' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_FONT
            ),
            'docBackgroundColor' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_DOC_BG_COLOR
            ),
            'dynamicStyling' => $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_STYLING
            ),
        ];
    }

    /**
     * Get display type for review product.
     *
     * @param mixed $website
     *
     * @return mixed
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
     * @param $path
     * @param $website
     *
     * @return mixed
     */
    public function getReviewWebsiteSettings($path, $website)
    {
        return $this->getWebsiteConfig($path, $website);
    }

    /**
     * @param $website
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
     * @param $website
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
     * @param $website
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
     * @param $website
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
     * @param $website
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
     * @param $website
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
     * check if both frotnend and backend secure(HTTPS).
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
     * Get current connector version.
     *
     * @return mixed
     */
    public function getConnectorVersion()
    {
        return $this->moduleInterface->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * Get the abandoned cart limit.
     *
     * @return mixed
     */
    public function getAbandonedCartLimit()
    {
        $cartLimit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT
        );
        
        return $cartLimit;
    }

    /**
     * @param $cronJob
     *
     * @return bool
     */
    public function getDateLastCronRun($cronJob)
    {
        $collection = $this->schelduleFactory->create()
            ->getCollection()
            ->addFieldToFilter('status', \Magento\Cron\Model\Schedule::STATUS_SUCCESS)
            ->addFieldToFilter('job_code', $cronJob);
        //limit and order the results
        $collection->getSelect()
            ->limit(1)
            ->order('executed_at DESC');

        if ($collection->getSize() == 0) {
            return false;
        }
        //@codingStandardsIgnoreStart
        $executedAt = $collection->getFirstItem()->getExecutedAt();
        //@codingStandardsIgnoreEnd

        return $executedAt;
    }

    /**
     * Get website datafields for subscriber
     *
     * @param $website
     * @return array
     */
    public function getWebsiteSalesDataFields($website)
    {
        $subscriberDataFileds = [
            'website_name' => '',
            'store_name' => '',
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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        $mappedData = array_intersect_key($mappedData, $subscriberDataFileds);
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
     * @param $dateFrom
     * @param $dateTo
     * @return bool|string
     */
    public function validateDateRange($dateFrom, $dateTo)
    {
        if (!$this->validateDate($dateFrom) || !$this->validateDate($dateTo)) {
            return 'From or To date is not a valid date.';
        }
        if (strtotime($dateFrom) > strtotime($dateTo)) {
            return 'To Date cannot be earlier then From Date.';
        }
        return false;
    }

    /**
     * @param $date
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
     * @param $created
     * @return false|int
     */
    public function getDateDifference($created)
    {
        $now = $this->datetime->gmtDate();
        return strtotime($now) - strtotime($created);
    }
}
