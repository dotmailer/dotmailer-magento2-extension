<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Helper\Config as EmailConfig;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var object
     */
    protected $_context;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var object
     */
    protected $_backendConfig;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $_productMetadata;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_adapter;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_store;


    /**
     * @var \Zend\Log\Logger
     */
    protected $_connectorLogger;

    /**
     * @var
     */
    protected $logFileName = 'connector.log';


    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\ProductMetadata      $productMetadata
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Magento\Config\Model\ResourceModel\Config  $resourceConfig
     * @param \Magento\Framework\App\ResourceConnection   $adapter
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param \Magento\Customer\Model\CustomerFactory     $customerFactory
     * @param \Magento\Backend\Model\Auth\Session         $sessionModel
     * @param \Magento\Store\Model\Store                  $store
     */
    public function __construct(
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $adapter,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\Store $store
    ) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/' . $this->logFileName);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->_connectorLogger = $logger;
        $this->_adapter = $adapter;
        $this->_productMetadata = $productMetadata;
        $this->_contactFactory = $contactFactory;
        $this->_resourceConfig = $resourceConfig;
        $this->_storeManager = $storeManager;
        $this->_objectManager = $objectManager;
        $this->customerFactory = $customerFactory;
        $this->_store = $store;

        parent::__construct($context);
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
        $website = $this->_storeManager->getWebsite($website);
        $enabled = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website
        );

        return (bool)$enabled;
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
        return $this->_storeManager->getStores($default);
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
    protected function _getConfigValue(
        $path,
        $contextScope = 'default',
        $contextScopeId = null
    ) {
        $config = $this->scopeConfig->getValue(
            $path, $contextScope, $contextScopeId
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
            return $this->_storeManager->getWebsite($websiteId);
        }

        return $this->_storeManager->getWebsite();
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
            $scope, $scopeId
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
        $this->_resourceConfig->saveConfig(
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
        $this->_resourceConfig->saveConfig(
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
     * Log data into system.log file.
     *
     * @param $data
     */
    public function log($data)
    {
        $this->_connectorLogger->info($data);
    }

    /**
     * Log data into debug.log file.
     *
     * @param $title
     * @param $context
     */
    public function debug($title, $context)
    {
        $this->_connectorLogger->debug($title, $context);
    }

    /**
     * Log data into the exception log file.
     *
     * @param $title
     * @param $error
     */
    public function error($title, $error)
    {
        $this->_connectorLogger->debug($title, $error);
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
        $contact = $this->_contactFactory->create()
            ->loadByCustomerEmail($email, $websiteId);
        if ($contactId = $contact->getContactId()) {
            return $contactId;
        }

        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->postContacts($email);

        if (isset($response->message)) {
            return false;
        }
        //save contact id
        if (isset($response->id)) {
            $contact->setContactId($response->id)
                ->save();
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
     * @return bool|mixed
     */
    public function getWebsiteApiClient($website = 0, $username = '', $password = '')
    {
        if ($username && $password) {
            $apiUsername = $username;
            $apiPassword = $password;
        } else {
            $apiUsername = $this->getApiUsername($website);
            $apiPassword = $this->getApiPassword($website);

            if (!$apiUsername || !$apiPassword) {
                return false;
            }
        }

        $client = $this->_objectManager->create(
            'Dotdigitalgroup\Email\Model\Apiconnector\Client',
            ['username' => $apiUsername, 'password' => $apiPassword]
        );

        return $client;
    }

    /**
     * Api username from config.
     *
     * @param int /object $website
     *
     * @return mixed
     */
    public function getApiUsername($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_USERNAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
        );
    }

    /**
     * Get api password from config.
     *
     * @param int $website
     *
     * @return string
     */
    public function getApiPassword($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
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
        $website = $this->_storeManager->getWebsite($website);

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
        $website = $this->_storeManager->getWebsite($website);

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
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getid()
        );
    }

    /**
     * Allow to set the resource settings from config.
     *
     * @return $this
     */
    public function allowResourceFullExecution()
    {
        if ($this->isResourceAllocationEnabled()) {

            /* it may be needed to set maximum execution time of the script to longer,
             * like 60 minutes than usual */
            //@codingStandardsIgnoreStart
            set_time_limit(7200);

            /* and memory to 512 megabytes */
            ini_set('memory_limit', '512M');
            //@codingStandardsIgnoreEnd
        }

        return $this;
    }

    /**
     * Use recommended resource allocation.
     *
     * @return bool
     */
    public function isResourceAllocationEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_RESOURCE_ALLOCATION
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
        return $this->_storeManager->getWebsites($default);
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

        return unserialize($attr);
    }

   

    /**
     * Get callback authorization link.
     *
     * @return mixed
     */
    public function getRedirectUri()
    {
        //Circular dependency when using di
        $callback = $this->_objectManager->create('Dotdigitalgroup\Email\Helper\Config')
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
     * Get website level config.
     *
     * @param     $path
     * @param int $website
     *
     * @return mixed
     */
    public function getWebsiteConfig($path, $website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);

        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $website->getId()
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
        $contactModel = $this->_contactFactory->create();
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
            $scopeId = $this->_storeManager->getWebsite($website)->getId();
        } else {
            $scope = 'default';
        }
        $this->_resourceConfig->saveConfig(
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
        $website = ($website) ? $this->_storeManager->getWebsite($website) : 0;

        $defaultGroup = $this->_storeManager->getWebsite($website)
            ->getDefaultGroup();

        if (!$defaultGroup) {
            return $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB
            );
        }

        //base url
        $baseUrl = $this->_storeManager->getStore(
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
        $salesTable = $this->_adapter->getTableName('sales_order');
        $adapter = $this->_adapter->getConnection('read');
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
        $quoteTable = $this->_adapter->getTableName('quote');
        $adapter = $this->_adapter->getConnection('read');
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
        if ($storeName = $website->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME
        )
        ) {
            $data[] = [
                'Key' => $storeName,
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
            $client = $this->getWebsiteApiClient($website);
            $client->updateContactDatafieldsByEmail($email, $data);
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
        $website = $this->_storeManager->getWebsite($website);

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
        $website = $this->_storeManager->getWebsite($website);

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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId()
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
     * @param int $websiteId
     *
     * @return mixed
     */
    public function getAutomationIdByType($automationType, $websiteId = 0)
    {
        $path = constant(
            EmailConfig::class . '::' . $automationType
        );
        $automationCampaignId = $this->getWebsiteConfig($path, $websiteId);

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
        $website = $this->_storeManager->getWebsite($websiteId);
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
        $store = $this->_storeManager->getStore();

        return $this->scopeConfig->isSetFlag(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_MAILCHECK_ENABLED,
            'store', $store
        );
    }

    /**
     * Get url for email capture.
     *
     * @return mixed
     */
    public function getEmailCaptureUrl()
    {
        return $this->_storeManager->getStore()->getUrl(
            'connector/ajax/emailcapture'
        );
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
                ',', $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_NAME_STYLE
            )),
            'priceStyle' => explode(
                ',', $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_PRICE_STYLE
            )),
            'linkStyle' => explode(
                ',', $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_LINK_STYLE
            )),
            'otherStyle' => explode(
                ',', $this->_getConfigValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_OTHER_STYLE
            )),
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
        return $this->getReviewWebsiteSettings(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_NEW_PRODUCT,
            $website);
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
        return $this->getReviewWebsiteSettings(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_CAMPAIGN,
            $website);
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
        return $this->getReviewWebsiteSettings(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_DISPLAY_TYPE,
            $website);
    }

    /**
     * check if both frotnend and backend secure(HTTPS).
     *
     * @return bool
     */
    public function isFrontendAdminSecure()
    {
        $frontend = $this->_store->isFrontUrlSecure();
        $admin = $this->getWebsiteConfig(\Magento\Store\Model\Store::XML_PATH_SECURE_IN_ADMINHTML);
        $current = $this->_store->isCurrentlySecure();

        if ($frontend && $admin && $current) {
            return true;
        }

        return false;
    }
}
