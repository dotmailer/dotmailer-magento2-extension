<?php

namespace Dotdigitalgroup\Email\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_context;
	protected $_resourceConfig;
	protected $_storeManager;
	protected $_objectManager;
	protected $_backendConfig;

	public function __construct(
		\Magento\Config\Model\Resource\Config $resourceConfig,
		\Magento\Framework\App\Resource $adapter,
		\Magento\Framework\UrlInterface $urlBuilder,
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Store\Model\StoreManagerInterface $storeManager
	)
	{
		$this->_adapter = $adapter;
		$this->_resourceConfig = $resourceConfig;
		$this->_storeManager = $storeManager;
		$this->_objectManager = $objectManager;

		parent::__construct($context);
	}

	protected function _getConfigValue($path, $contextScope, $contextScopeId = null) {

		$config = $this->scopeConfig->getValue($path, $contextScope, $contextScopeId);

		return $config;
	}

    /**
     * Get api creadentials enabled.
     *
     * @param int $website
     *
     * @return mixed
     */
    public function isEnabled($website = 0)
    {
        $website = $this->_storeManager->getWebsite($website);
        $enabled = $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED,
	        \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
	        $website);

        return $enabled;
    }

    /**
     * @param int/object $website
     * @return mixed
     */
    public function getApiUsername($website = 0)
    {
	    $website = $this->_storeManager->getWebsite($website);
	    return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_USERNAME,
		    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
		    $website->getId()
	    );
    }

    public function getApiPassword($website = 0)
    {
	    $website = $this->_storeManager->getWebsite($website);
		return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_PASSWORD,
			\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
			$website->getId()
		);
    }

	/**
	 * Get all websites.
	 * @param bool|false $default
	 *
	 * @return \Magento\Store\Api\Data\WebsiteInterface[]
	 */
    public function getWebsites($default = false)
    {
        return $this->_storeManager->getWebsites($default);
    }

	/**
	 * Get all stores.
	 * @param bool|false $default
	 *
	 * @return \Magento\Store\Api\Data\StoreInterface[]
	 */
	public function getStores( $default = false )
	{
		return $this->_storeManager->getStores($default);
	}

    public function auth($authRequest)
    {
        if ($authRequest != $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE)) {

            //throw new Exception('Authentication failed : ' . $authRequest);
            return false;
        }
        return true;
    }

    public function getMappedCustomerId()
    {
	    return $this->_getConfigValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID, 'default');
    }

    public function getMappedOrderId()
    {
        return $this->_getConfigValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_ORDER_ID, 'default');
    }

	/**
	 * Get website selected in admin.
	 * @return \Magento\Store\Api\Data\WebsiteInterface
	 */
	public function getWebsite()
	{
		$websiteId = $this->_request->getParam('website', false);
		if ($websiteId)
			return $this->_storeManager->getWebsite($websiteId);

		return $this->_storeManager->getWebsite();
	}

	/**
	 *
	 * @return mixed
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

	    $passcode = $this->_getConfigValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE, $scope, $scopeId);

	    $this->_logger->debug($passcode);

	    return $passcode;
    }

	/**
	 * Save config data.
	 * @param $path
	 * @param $value
	 * @param $scope
	 * @param $scopeId
	 */
	public function saveConfigData($path, $value, $scope, $scopeId )
	{
		$this->_resourceConfig->saveConfig(
			$path,
			$value,
			$scope,
			$scopeId
		);
	}

	public function disableTransactionalDataConfig( $scope, $scopeId )
	{
		$this->_resourceConfig->saveConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
			0,
			$scope,
			$scopeId
		);
	}

	/**
	 * Customer last order id.
	 *
	 * @return mixed
	 */
    public function getLastOrderId()
    {
	    return $this->_getConfigValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID, 'default');
    }

    public function getLastQuoteId()
    {
	    return $this->_getConfigValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID, 'default');
    }

    public function log($data, $filename = 'api.log')
    {

	    $this->_logger->info($data);
    }

	public function debug( $title, $context )
	{
		$this->_logger->debug($title, $context);

	}

    public function getDebugEnabled()
    {
	    return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED);
    }

	/**
	 * Extension version number.
	 * @return string
	 */
	public function getConnectorVersion()
	{
		//@todo get the module version from the config
		return'';
		$modules = (array) Mage::getConfig()->getNode('modules')->children();
		if (isset($modules['Dotdigitalgroup_Email'])) {
			$moduleName = $modules['Dotdigitalgroup_Email'];
			return (string) $moduleName->version;
		}
		return '';
	}


    public function getPageTrackingEnabled()
    {
        return (bool)Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_PAGE_TRACKING_ENABLED);
    }

    public function getRoiTrackingEnabled()
    {
        return (bool)Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_ROI_TRACKING_ENABLED);
    }

    /**
     * Use recommended resource allocation.
     *
     * @return bool
     */
    public function getResourceAllocationEnabled()
    {
        return (bool)$this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_RESOURCE_ALLOCATION);
    }

    public function getMappedStoreName($website)
    {
        $mapped = $website->getConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_STORENAME);
        $storeName = ($mapped)? $mapped : '';
        return  $storeName;
    }

    /**
     * Get the contact id for the custoemer based on website id.
     * @param $email
     * @param $websiteId
     *
     * @return bool
     */
    public function getContactId($email, $websiteId)
    {
	    $contact = Mage::getModel('ddg_automation/contact')->loadByCustomerEmail($email, $websiteId);
	    if ($contactId = $contact->getContactId()) {
		    return $contactId;
	    }

        $client = $this->getWebsiteApiClient($websiteId);
        $response = $client->postContacts($email);

        if (isset($response->message))
            return false;
	    //save contact id
		if (isset($response->id)){
			$contact->setContactId($response->id)
				->save();
		}
        return $response->id;
    }

	/**
	 * Get the addres book for customer.
	 *
	 * @param int $website
	 *
	 * @return mixed
	 */
    public function getCustomerAddressBook($website = 0)
    {
	    $website = $this->_storeManager->getWebsite($website);

	    return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
		    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
		    $website);
    }

    public function getSubscriberAddressBook($website)
    {
	    $website = $this->_storeManager->getWebsite($website);
	    return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
		    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
		    $website->getId()
		    );
    }

	/**
	 * Guest address book.
	 * @param $website
	 *
	 * @return mixed
	 */
    public function getGuestAddressBook($website)
    {
	    $website = $this->_storeManager->getWebsite($website);

	    return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID,
		    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
		    $website->getid()
		    );
    }

    /**
     * @return $this
     */
    public  function allowResourceFullExecution()
    {
        if ($this->getResourceAllocationEnabled()) {

            /* it may be needed to set maximum execution time of the script to longer,
             * like 60 minutes than usual */
            set_time_limit(7200);

            /* and memory to 512 megabytes */
            ini_set('memory_limit', '512M');
        }
        return $this;
    }
    public function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    /**
     * @return string
     */
    public function getStringWebsiteApiAccounts()
    {
        $accounts = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteId = $website->getId();
            $apiUsername = $this->getApiUsername($website);
            $accounts[$apiUsername] = $apiUsername . ', websiteId: ' . $websiteId . ' name ' . $website->getName();
        }
        return implode('</br>', $accounts);
    }

    /**
     * @param int $website
     *
     * @return array|mixed
     */
    public function getCustomAttributes($website = 0)
    {
	    $attr = $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS,
		    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
		    $website->getId());

        if (!$attr)
            return array();

        return unserialize($attr);
    }


	/**
	 * Enterprise custom datafields attributes.
	 * @param int $website
	 *
	 * @return array
	 * @throws Mage_Core_Exception
	 */
	public function getEnterpriseAttributes( $website = 0) {
		$website = Mage::app()->getWebsite($website);
		$result = array();
		$attrs = $website->getConfig('connector_data_mapping/enterprise_data');
		//get individual mapped keys
		foreach ( $attrs as $key => $one ) {
			$config = $website->getConfig('connector_data_mapping/enterprise_data/' . $key);
			//check for the mapped field
			if ($config)
				$result[$key] = $config;
		}

		if (empty($result))
			return false;
		return $result;
	}

	/**
	 * Get website level config.
	 * @param $path
	 * @param int $website
	 *
	 * @return mixed
	 */
    public function getWebsiteConfig($path, $website = 0)
    {
	    $website = $this->_storeManager->getWebsite($website);
	    return $this->scopeConfig->getValue($path,
	        \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
		    $website->getId()
		    );
    }

	/**
	 * Api client by website.
	 *
	 * @param int $website
	 *
	 * @return bool
	 */
    public function getWebsiteApiClient($website = 0)
    {
	    $apiUsername = $this->getApiUsername($website);
	    $apiPassword = $this->getApiPassword($website);
        if (! $apiUsername || ! $apiPassword)
            return false;

	    $client = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Client', ['username' => $apiUsername, 'password' => $apiPassword]);

        return $client;
    }

    /**
     * Retrieve authorisation code.
     */
    public function getCode()
    {
        $adminUser = Mage::getSingleton('admin/session')->getUser();
        $code = $adminUser->getEmailCode();

        return $code;
    }

    /**
     * Autorisation url for OAUTH.
     * @return string
     */
    public function getAuthoriseUrl()
    {
        $clientId = Mage::getStoreConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_CLIENT_ID);

	    //callback uri if not set custom
	    $redirectUri = $this->getRedirectUri();
	    $redirectUri .= 'connector/email/callback';
	    $adminUser = Mage::getSingleton('admin/session')->getUser();
        //query params
        $params = array(
            'redirect_uri' => $redirectUri,
            'scope' => 'Account',
            'state' => $adminUser->getId(),
            'response_type' => 'code'
        );

        $authorizeBaseUrl = Mage::helper('ddg/config')->getAuthorizeLink();
        $url = $authorizeBaseUrl . http_build_query($params) . '&client_id=' . $clientId;

        return $url;
    }

	public function getRedirectUri()
	{
		$callback = Mage::helper('ddg/config')->getCallbackUrl();

		return $callback;
	}

    /**
     * order status config value
     * @param int $website
     * @return mixed order status
     */
    public function getConfigSelectedStatus($website = 0)
    {
        $status = $this->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS, $website);
        if($status)
            return explode(',',$status);
        else
            return false;
    }

    public function getConfigSelectedCustomOrderAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOM_ORDER_ATTRIBUTES, $website);
        if($customAttributes)
            return explode(',',$customAttributes);
        else
            return false;
    }

    public function getConfigSelectedCustomQuoteAttributes($website = 0)
    {
        $customAttributes = $this->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOM_QUOTE_ATTRIBUTES, $website);
        if($customAttributes)
            return explode(',',$customAttributes);
        else
            return false;
    }


    public function setConnectorContactToReImport($customerId)
    {
        $contactModel = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact');
        $contactModel
            ->loadByCustomerId($customerId)
            ->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED)
            ->save();
    }

    /**
     * Diff between to times;
     *
     * @param $time1
     * @param $time2
     * @return int
     */
    public function dateDiff($time1, $time2=NULL) {
        if (is_null($time2)) {
            $time2 = Mage::getModel('core/date')->date();
        }
        $time1 = strtotime($time1);
        $time2 = strtotime($time2);
        return $time2 - $time1;
    }


    /**
     * Disable website config when the request is made admin area only!
     * @param $path
     *
     */
    public function disableConfigForWebsite($path)
    {
        $scopeId = 0;
        if ($website = $this->_request->getRequest()->getParam('website')) {
            $scope = 'websites';
            $scopeId = $this->_storeManager->getWebsite($website)->getId();
        } else {
            $scope = "default";
        }
	    $this->_resourceConfig->saveConfig(
		    $path,
		    0,
		    $scope,
		    $scopeId
	    );
    }

    /**
     * number of customers with duplicate emails, emails as total number
     */
    public function getCustomersWithDuplicateEmails( )
    {
	    $customers = $this->_objectManager->create('Magento\Customer\Model\Customer')->getCollection();

        //duplicate emails
        $customers->getSelect()
            ->columns(array('emails' => 'COUNT(e.entity_id)'))
            ->group('email')
            ->having('emails > ?', 1);

        return $customers;
    }


    /**
     * Generate the baseurl for the default store
     * dynamic content will be displayed
     * @return string
     */
	public function generateDynamicUrl()
	{
		$website = $this->_request->getParam('website', false);

		//set website url for the default store id
		$website = ($website)? $this->_storeManager->getWebsite( $website ) : 0;

		$defaultGroup = $this->_storeManager->getWebsite($website)
		                    ->getDefaultGroup();

		if (! $defaultGroup)
			return $mage = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);

		//base url
		$baseUrl = $this->_storeManager->getStore($defaultGroup->getDefaultStore())->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);

		return $baseUrl;
	}

    /**
     *
     *
     * @param int $store
     * @return mixed
     */
    public function isNewsletterSuccessDisabled($store = 0)
    {
        return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS, 'store', $store);
    }

    /**
     * get sales_flat_order table description
     *
     * @return array
     */
    public function getOrderTableDescription()
    {

	    $salesTable  = $this->_adapter->getTableName('sales_order');
	    $adapter = $this->_adapter->getConnection('read');
	    $columns = $adapter->describeTable($salesTable);

	    return $columns;
    }

    /**
     * get sales_flat_quote table description
     *
     * @return array
     */
    public function getQuoteTableDescription()
    {

	    $quoteTable  = $this->_adapter->getTableName('quote');
	    $adapter = $this->_adapter->getConnection('read');
	    $columns = $adapter->describeTable($quoteTable);

	    return $columns;
    }

    /**
     * @return bool
     */
    public function getEasyEmailCapture()
    {
        return (bool) $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE);
    }

	/**
	 * @return bool
	 */
	public function getEasyEmailCaptureForNewsletter()
	{
		return (bool) $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER);
	}
    /**
     * get feefo logon config value
     *
     * @return mixed
     */
    public function getFeefoLogon()
    {
        return $this->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_LOGON);
    }

    /**
     * get feefo reviews limit config value
     *
     * @return mixed
     */
    public function getFeefoReviewsPerProduct()
    {
        return $this->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_REVIEWS);
    }

    /**
     * get feefo logo template config value
     *
     * @return mixed
     */
    public function getFeefoLogoTemplate()
    {
        return $this->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_FEEFO_TEMPLATE);
    }

	/**
	 * update data fields
	 *
	 * @param $email
	 * @param $storeName
	 */
	public function updateDataFields($email, $website, $storeName)
    {
        $data = array();
        if($store_name = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME)){
            $data[] = array(
                'Key' => $store_name,
                'Value' => $storeName
            );
        }
        if($website_name = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME)){
            $data[] = array(
                'Key' => $website_name,
                'Value' => $website->getName()
            );
        }
        if(!empty($data)){
            //update data fields
            $client = $this->getWebsiteApiClient($website);
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }

    /**
     * check connector SMTP installed/active status
     * @return boolean
     */
    public function isSmtpEnabled()
    {
	    //@todo get the module config
	    return false;
        //return (bool)Mage::getConfig()->getModuleConfig('Ddg_Transactional')->is('active', 'true');
    }

	/**
	 * Is magento enterprise.
	 * @return bool
	 */
	public function isEnterprise()
	{
		return Mage::getConfig ()->getModuleConfig ( 'Enterprise_Enterprise' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_AdminGws' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Checkout' ) && Mage::getConfig ()->getModuleConfig ( 'Enterprise_Customer' );

	}

    public function getTemplateList()
    {
        $client = $this->getWebsiteApiClient(Mage::app()->getWebsite());
        if(!$client)
            return array();

        $templates = $client->getApiTemplateList();
        $fields[] = array('value' => '', 'label' => '');
        foreach ( $templates as $one ) {
            if ( isset( $one->id ) ) {
                $fields[] = array(
                    'value' => $one->id,
                    'label' => $this->__( addslashes( $one->name ) )
                );
            }
        }
        return $fields;
    }

	/**
	 * Update last quote id datafield.
	 * @param $quoteId
	 * @param $email
	 * @param $websiteId
	 */
	public function updateLastQuoteId($quoteId, $email, $websiteId)
	{
		$client = $this->getWebsiteApiClient($websiteId);
		//last quote id config data mapped
		$quoteIdField = $this->getLastQuoteId();

		$data[] = array(
			'Key' => $quoteIdField,
			'Value' => $quoteId
		);
		//update datafields for conctact
		$client->updateContactDatafieldsByEmail($email, $data);
	}

	/**
	 * Remove code and disable Raygun.
	 */
	public function disableRaygun()
	{
		$config = new Mage_Core_Model_Config();
		$config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_RAYGUN_APPLICATION_CODE, '');
		Mage::getConfig()->cleanCache();
	}

	public function enableRaygunCode()
	{
		$curl = new Varien_Http_Adapter_Curl();
		$curl->setConfig(array(
			'timeout'   => 2
		));
		$curl->write(Zend_Http_Client::GET, Dotdigitalgroup_Email_Helper_Config::RAYGUN_API_CODE_URL, '1.0');
		$data = $curl->read();

		if ($data === false) {
			return false;
		}
		$data = preg_split('/^\r?$/m', $data, 2);
		$data = trim($data[1]);
		$curl->close();

		$xml  = new SimpleXMLElement($data);
		$raygunCode = $xml->code;

		//not found
		if (!$raygunCode)
			return;

		$config = new Mage_Core_Model_Config();
		$config->saveConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_RAYGUN_APPLICATION_CODE, $raygunCode);
	}

	/**
	 * Send the exception to raygun.
	 *
	 * @param $e Exception
	 */
	public function sendRaygunException( $e )
	{
		if (!$this->raygunEnabled())
			return;
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$tags = array(
			$baseUrl,
			Mage::getVersion()
		);

		$client = $this->getRaygunClient();
		//user, firstname, lastname, email, annonim, uuid
		$client->SetUser($baseUrl, null, null, $this->getApiUsername());
		$client->SetVersion($this->getConnectorVersion());
		$client->SendException($e, $tags);
	}

	/**
	 * Get order sync enabled value from configuration.
	 * @param int $websiteId
	 *
	 * @return bool
	 */
	public function getOrderSyncEnabled($websiteId = 0)
	{
		return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
			\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
			$websiteId
			);
	}
	/**
	 * Get the catalog sync enabled value from config.
	 * @param int $websiteId
	 *
	 * @return bool
	 */
	public function getCatalogSyncEnabled($websiteId = 0)
	{
		return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED,
			\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
			$websiteId
			);
	}

	/**
     * Customer sync enabled.
	 * @param int $website
	 *
	 * @return bool
	 */
	public function getCustomerSyncEnabled($website = 0)
	{
		$website = $this->_storeManager->getWebsite($website);

        $enabled = $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED,
	        \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
	        $website);

        return $enabled;
	}

	/**
	 * Customer sync size limit.
	 * @param int $website
	 *
	 * @return mixed
	 */
	public function getSyncLimit($website = 0)
	{
		$website = $this->_storeManager->getWebsite($website);
		return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
			\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
			$website);
	}

	/**
	 * Get the guest sync enabled value.
	 *
	 * @param int $websiteId
	 *
	 * @return bool
	 */
	public function getGuestSyncEnabled($websiteId = 0)
	{
		return  $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED,
			\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
			$websiteId
			);
	}

	/**
	 * @param int $websiteId
	 *
	 * @return bool
	 */
	public function getSubscriberSyncEnabled($websiteId = 0)
	{
		return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED,
			\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
			$websiteId
			);
	}

	/**
	 * @return bool
	 */
	public function getCronInstalled()
	{
		$lastCustomerSync = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Cron')
			->getLastCustomerSync();
		return true;
		$timespan = $this->_helper->dateDiff($lastCustomerSync);

		//last customer cron was less then 15 min
		if ($timespan <= 15 * 60) {
			return true;
		}
		return false;
	}
	/**
	 * Get the config id by the automation type.
	 * @param $automationType
	 * @param int $websiteId
	 *
	 * @return mixed
	 */
	public function getAutomationIdByType($automationType, $websiteId = 0)
	{
		$path = constant('Dotdigitalgroup_Email_Helper_Config::' . $automationType);
		$automationCampaignId = $this->getWebsiteConfig($path, $websiteId);

		return $automationCampaignId;
	}

    public function getAbandonedProductName()
    {
	    return $this->scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_PRODUCT_NAME);
    }

    /**
     * api- update the product name most expensive.
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
            $data[] = array(
                'Key' => $field,
                'Value' => $name
            );
            //update data field for contact
            $client->updateContactDatafieldsByEmail($email, $data);
        }
    }


	/**
	 * Api request response time limit that should be logged.
	 *
	 * @param int $websiteId
	 *
	 * @return mixed
	 */
	public function getApiResponseTimeLimit($websiteId = 0)
	{
		$website = $this->_storeManager->getWebsite($websiteId);
		$limit = $website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DEBUG_API_REQUEST_LIMIT);

		return $limit;
	}

	/**
	 * Main email for an account.
	 *
	 * @param int $website
	 *
	 * @return string
	 */
	public function getAccountEmail( $website = 0)
	{
		$client = $this->getWebsiteApiClient($website);
		$info =  $client->getAccountInfo();
		$email = '';

		$properties = $info->properties;

		foreach ( $properties as $property ) {

			if ($property->name == 'MainEmail')
				$email = $property->value;
		}
		return $email;
	}
}
