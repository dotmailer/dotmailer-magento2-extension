<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use \Psr\Log\LoggerInterface;

class Contact
{
	private $_start;
	private $_countCustomers = 0;
	private $_sqlExecuted = false;

	protected $_logger;
	protected $_helper;
	protected $_registry;
	protected $messageManager;
	protected $_storeManager;
	protected $_scopeConfig;
	protected $contactCollection;
	protected $_resource;


	public function __construct(
		LoggerInterface $logger,
		\Magento\Framework\App\Resource $resource,
		\Dotdigitalgroup\Email\Helper\File   $file,
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Backend\App\Action\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Dotdigitalgroup\Email\Helper\Config $config,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Customer\Model\Resource\Customer\CollectionFactory $customerCollectionFactory,
		\Dotdigitalgroup\Email\Model\Resource\Contact\CollectionFactory $contactCollectionFactory
	)
	{
		$this->_file = $file;
		$this->_config = $config;
		$this->_logger = $logger;
		$this->_registry = $registry;
		$this->_storeManager = $storeManagerInterface;
		$this->messageManager = $context->getMessageManager();
		$this->_helper = $helper;
		$this->_resource = $resource;
		$this->_scopeConfig = $scopeConfig;
		$this->_objectManager = $objectManager;

		$this->collection = $customerCollectionFactory->create();
		$this->collection->addAttributeToSelect('*');

		$this->contactCollection = $contactCollectionFactory->create();
	}
	/**
	 * Contact sync.
	 *
	 * @return array
	 */
	public function sync()
	{
		//customer campaign id from configuration
		$customerCampaign = $this->_scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID);

		//result message
		$result = array('success' => true, 'message' => '');
		//starting time for sync
		$this->_start = microtime(true);

		//resourse allocation
		$this->_helper->allowResourceFullExecution();

		//get websites collection
		$websites = $this->_helper->getWebsites();

		foreach ( $websites as $website ) {

			$enabled        = $this->_helper->isEnabled( $website );
			$customerSyncEnabled   = $this->_helper->getCustomerSyncEnabled( $website );

			if ($enabled && $customerSyncEnabled) {

				if (! $this->_countCustomers)
					$this->_helper->log('---------- Start customer sync ----------');
				$numUpdated = $this->exportCustomersForWebsite($website);
				// show message for any number of customers
				if ($numUpdated)
					$result['message'] .=  '</br>' . $website->getName() . ', updated customers = ' . $numUpdated;
			}
		}
		//sync proccessed
		if ($this->_countCustomers) {
			$message = 'Total time for sync : ' . gmdate( "H:i:s", microtime( true ) - $this->_start ) . ', Total updated = ' . $this->_countCustomers;
			$this->_helper->log( $message );
			$message .= $result['message'];
			$result['message'] = $message;
		}

		return $result;
	}

	/**
	 * Execute the contact sync for the website
	 * number of customer synced.
	 *
	 * @return int|void
	 */
	public function exportCustomersForWebsite( $website)
	{
		$customers = $headers = $allMappedHash = array();
		$pageSize = $this->_helper->getSyncLimit($website);

		$customerAddressBook = $this->_helper->getCustomerAddressBook($website);

		//skip if customer address book is not mapped
		if (! $customerAddressBook)
			return 0;

		$write = $this->_resource->getConnection('core_write');
		$contactTable = $this->_resource->getTableName('email_contact');
		$select = $write->select();
		$contactModel = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact');
		$contacts = $this->contactCollection
			->addFieldToSelect('*')
			->addFieldToFilter('website_id', $website->getId())
			->setPageSize($pageSize);

		// no contacts for this website
		if (!$contacts->getSize())
			return 0;

		//create customer filename
		$customersFile = strtolower($website->getCode() . '_customers_' . date('d_m_Y_Hi') . '.csv');
		$this->_helper->log('Customers file : ' . $customersFile);

		//get customer ids
		$customerIds = $contacts->getColumnValues('customer_id');


		//customer collection
		$customerCollection = $this->getCollection($customerIds, $website->getId());

		/**
		 * HEADERS.
		 */
		$mappedHash = $this->_file->getWebsiteCustomerMappingDatafields($website);
		$headers = $mappedHash;

		//custom customer attributes
		$customAttributes = $this->_helper->getCustomAttributes($website);
		if ($customAttributes){
			foreach ($customAttributes as $data) {
				$headers[] = $data['datafield'];
				$allMappedHash[$data['attribute']] = $data['datafield'];
			}
		}
		$headers[] = 'Email';
		$headers[] = 'EmailType';

		$this->_file->outputCSV($this->_file->getFilePath($customersFile), $headers);
		/**
		 * END HEADERS.
		 */
		$coreResource  = $this->_resource;
		//only execute once despite number of websites
		if (!$this->_sqlExecuted)  {
			//check subscriber and update in one query
			$select->joinLeft(
				array('s' => $coreResource->getTableName('newsletter_subscriber')),
				"c.customer_id = s.customer_id",
				array('subscriber_status' => 's.subscriber_status')
			);
			//update sql statement
			$updateSql = $select->crossUpdateFromSelect(array('c' => $contactTable));
			//run query and update subscriber_status column
			$write->query($updateSql);
			//update is_subscriber column if subscriber_status is not null
			$write->update($contactTable, array('is_subscriber' => 1), "subscriber_status is not null");


			//remove contact with customer id set and no customer
			$select->reset()
			       ->from(
				       array('c' => $contactTable),
				       array('c.customer_id')
			       )
			       ->joinLeft(
				       array('e' => $coreResource->getTableName('customer_entity')),
				       "c.customer_id = e.entity_id"
			       )
			       ->where('e.entity_id is NULL');
			//delete sql statement
			$deleteSql = $select->deleteFromSelect('c');
			//run query
			$write->query($deleteSql);

			//set flag
			$this->_sqlExecuted = true;
		}

		foreach ($customerCollection as $customer) {

			$connectorCustomer = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Customer' );
			$connectorCustomer->setMappingHash($mappedHash);
			$connectorCustomer->setCustomerData($customer);

			//count number of customers
			$customers[] = $customer->getId();

			if ($connectorCustomer) {
				foreach ($customAttributes as $data) {
					$attribute = $data['attribute'];
					$value = $customer->getData($attribute);
					$connectorCustomer->setData($value);
				}
			}

			//contact email and email type
			$connectorCustomer->setData($customer->getEmail());
			$connectorCustomer->setData('Html');
			// save csv file data for customers
			$this->_file->outputCSV($this->_file->getFilePath($customersFile), $connectorCustomer->toCSVArray());


			//clear collection and free memory
			$customer->clearInstance();
		}

		$customerNum = count($customers);
		$this->_helper->log('Website : ' . $website->getName() . ', customers = ' . $customerNum);
		$this->_helper->log('---------------------------- execution time :' . gmdate("H:i:s", microtime(true) - $this->_start));

		if (is_file($this->_file->getFilePath($customersFile))) {
			if ($customerNum > 0) {
				//register in queue with importer
				$check = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
					\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_CONTACT,
					'',
					\Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
					$website->getId(),
					$customersFile
				);

				//set imported
				if ($check) {
					$tableName = $this->_resource->getTableName('email_contact');
					$ids = implode(', ', $customers);
					$write->update($tableName, array('email_imported' => 1), "customer_id IN ($ids)");
				}
			}
		}
		$this->_countCustomers += $customerNum;
		return $customerNum;
	}

	/**
	 * Sync a single contact.
	 *
	 * @param null $contactId
	 *
	 * @return mixed
	 */
	public function syncContact($contactId = null)
	{
		if ($contactId)
			$contact = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact')->load($contactId);
		else {
			$contact = $this->_registry->registry('current_contact');
		}
		if (! $contact->getId()) {
			$this->messageManager->addError('No contact found!');
			return false;
		}

		$websiteId = $contact->getWebsiteId();
		$website = $this->_storeManager->getWebsite($websiteId);
		$updated = 0;
		$customers = $headers = $allMappedHash = array();
		$this->_helper->log('---------- Start single customer sync ----------');
		//skip if the mapping field is missing
		if(!$this->_helper->getCustomerAddressBook($website))
			return false;
		//$fileHelper = Mage::helper('ddg/file');

		$customerId = $contact->getCustomerId();
		if (!$customerId) {
			$this->_storeManager->addError('Cannot manually sync guests!');
			return false;
		}
		$client = $this->_helper->getWebsiteApiClient($website);

		//create customer filename
		$customersFile = strtolower($website->getCode() . '_customers_' . date('d_m_Y_Hi') . '.csv');
		$this->_helper->log('Customers file : ' . $customersFile);

		/**
		 * HEADERS.
		 */
		$mappedHash = $this->_file->getWebsiteCustomerMappingDatafields($website);
		$headers = $mappedHash;
		//custom customer attributes
		$customAttributes = $this->_helper->getCustomAttributes($website);
		foreach ($customAttributes as $data) {
			$headers[] = $data['datafield'];
			$allMappedHash[$data['attribute']] = $data['datafield'];
		}

		$headers[] = 'Email';
		$headers[] = 'EmailType';
		$this->_file->outputCSV($this->_file->getFilePath($customersFile), $headers);
		/**
		 * END HEADERS.
		 */
		$customerCollection = $this->getCollection(array($customerId), $website->getId());

		foreach ($customerCollection as $customer) {
			$contactModel = $this->_objectManager->create('email_contact');

			$contactModel = $this->loadByCustomerEmail($customer->getEmail(), $websiteId);
			//skip contacts without customer id
			if (!$contactModel->getId())
				continue;
			/**
			 * DATA.
			 */
			$connectorCustomer = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Customer');
			$connectorCustomer->setMappingHash($mappedHash);
			$connectorCustomer->setCustomerData($customer);
			//count number of customers
			$customers[] = $connectorCustomer;
			foreach ($customAttributes as $data) {
				$attribute = $data['attribute'];
				$value = $customer->getData($attribute);
				$connectorCustomer->setData($value);
			}
			//contact email and email type
			$connectorCustomer->setData($customer->getEmail());
			$connectorCustomer->setData('Html');
			// save csv file data for customers
			$this->_file->outputCSV($this->_file->getFilePath($customersFile), $connectorCustomer->toCSVArray());

			/**
			 * END DATA.
			 */

			//mark the contact as imported
			$contactModel->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED);
			$subscriber = $this->_objectManager->create('Magento\Newsletter\Model\Subscriber')->loadByEmail($customer->getEmail());
			if ($subscriber->isSubscribed()) {
				$contactModel->setIsSubscriber('1')
					->setSubscriberStatus($subscriber->getSubscriberStatus());
			}

			$contactModel->save();
			$updated++;
		}

		if (is_file($this->_file->getFilePath($customersFile))) {
			//import contacts
			if ($updated > 0) {
				//register in queue with importer
				$this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
					\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_CONTACT,
					'',
					\Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
					$website->getId(),
					$customersFile
				);
				$client->postAddressBookContactsImport($customersFile,   $this->_helper->getCustomerAddressBook($website));
			}
		}
		return $contact->getEmail();
	}


	public function getCollection($customerIds, $websiteId = 0)
	{
		//$customerCollection = Mage::getResourceModel('customer/customer_collection')
		$customerCollection = $this->collection->addNameToSelect()
			->addAttributeToSelect('*')
			->joinAttribute('billing_street',       'customer_address/street',      'default_billing', null, 'left')
			->joinAttribute('billing_city',         'customer_address/city',        'default_billing', null, 'left')
			->joinAttribute('billing_country_code', 'customer_address/country_id',  'default_billing', null, 'left')
			->joinAttribute('billing_postcode',     'customer_address/postcode',    'default_billing', null, 'left')
			->joinAttribute('billing_telephone',    'customer_address/telephone',   'default_billing', null, 'left')
			->joinAttribute('billing_region',       'customer_address/region',      'default_billing', null, 'left')
			->joinAttribute('shipping_street',      'customer_address/street',      'default_shipping', null, 'left')
			->joinAttribute('shipping_city',        'customer_address/city',        'default_shipping', null, 'left')
			->joinAttribute('shipping_country_code','customer_address/country_id',  'default_shipping', null, 'left')
			->joinAttribute('shipping_postcode',    'customer_address/postcode',    'default_shipping', null, 'left')
			->joinAttribute('shipping_telephone',   'customer_address/telephone',   'default_shipping', null, 'left')
			->joinAttribute('shipping_region',      'customer_address/region',      'default_shipping', null, 'left')
			->addAttributeToFilter('entity_id', array('in' => $customerIds));

		$customer_log                   = $this->_resource->getTableName('log_customer');
		$eav_attribute                  = $this->_resource->getTableName('eav_attribute');
		$quote                          = $this->_resource->getTableName('quote');
		$sales_order                     = $this->_resource->getTableName('sales_order');
		$sales_order_grid               = $this->_resource->getTableName('sales_order_grid');
		$sales_order_item                = $this->_resource->getTableName('sales_order_item');
		$eav_attribute_option_value     = $this->_resource->getTableName('eav_attribute_option_value');
		$catalog_product_entity_int     = $this->_resource->getTableName('catalog_product_entity_int');
		$catalog_category_product_index = $this->_resource->getTableName('catalog_category_product');

		// get the last login date from the log_customer table
		$customerCollection->getSelect()->columns(
			array('last_logged_date' => new \Zend_Db_Expr ("(SELECT login_at FROM  $customer_log WHERE customer_id =e.entity_id ORDER BY log_id DESC LIMIT 1)")));

		// customer order information
		$alias = 'subselect';
		//@todo fix the stautues datafields to sync and sales values
		$statuses = $this->_helper->getWebsiteConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS, $websiteId
		);
		$orderTable = $this->_resource->getTableName('sales_order');
		//$subselect = Mage::getModel('Varien_Db_Select', Mage::getSingleton('core/resource')->getConnection('core_read'))
//		$subselect = $this->collection->getSelect()
//		                 ->from($orderTable, array(
//				                 'customer_id as s_customer_id',
//				                 'sum(grand_total) as total_spend',
//				                 'count(*) as number_of_orders',
//				                 'avg(grand_total) as average_order_value',
//			                 )
//		                 )
//		                 ->where("status in (?)", $statuses)
//		                 ->group('customer_id')
//		;

		$customerCollection->getSelect()->columns(array(
				'last_order_date' => new \Zend_Db_Expr("(SELECT created_at FROM $sales_order_grid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"),
				'last_order_id' => new \Zend_Db_Expr("(SELECT entity_id FROM $sales_order_grid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"),
				'last_increment_id' => new \Zend_Db_Expr("(SELECT increment_id FROM $sales_order_grid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"),
				'last_quote_id' => new \Zend_Db_Expr("(SELECT entity_id FROM $quote WHERE customer_id = e.entity_id ORDER BY created_at DESC LIMIT 1)"),
				'first_category_id' => new \Zend_Db_Expr(
					"(
                        SELECT ccpi.category_id FROM $sales_order as sfo
                        left join $sales_order_item as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalog_category_product_index as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
				),
				'last_category_id' => new \Zend_Db_Expr(
					"(
                        SELECT ccpi.category_id FROM $sales_order as sfo
                        left join $sales_order_item as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalog_category_product_index as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
				),
				'product_id_for_first_brand' => new \Zend_Db_Expr(
					"(
                        SELECT sfoi.product_id FROM $sales_order as sfo
                        left join $sales_order_item as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_id = e.entity_id and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
				),
				'product_id_for_last_brand' => new \Zend_Db_Expr(
					"(
                        SELECT sfoi.product_id FROM $sales_order as sfo
                        left join $sales_order_item as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_id = e.entity_id and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
				),
				'week_day' => new \Zend_Db_Expr(
					"(
                        SELECT dayname(created_at) as week_day
                        FROM $sales_order
                        WHERE customer_id = e.entity_id
                        GROUP BY week_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
				),
				'month_day' => new \Zend_Db_Expr(
					"(
                        SELECT monthname(created_at) as month_day
                        FROM $sales_order
                        WHERE customer_id = e.entity_id
                        GROUP BY month_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
				),
				'most_category_id' => new \Zend_Db_Expr(
					"(
                        SELECT ccpi.category_id FROM $sales_order as sfo
                        LEFT JOIN $sales_order_item as sfoi on sfoi.order_id = sfo.entity_id
                        LEFT JOIN $catalog_category_product_index as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id AND ccpi.category_id is not null
                        GROUP BY category_id
                        HAVING COUNT(sfoi.product_id) > 0
                        ORDER BY COUNT(sfoi.product_id) DESC
                        LIMIT 1
                    )"
				),
				'most_brand' => new \Zend_Db_Expr(
					"(
                        SELECT eaov.value from $sales_order sfo
                        LEFT JOIN $sales_order_item as sfoi on sfoi.order_id = sfo.entity_id
                        LEFT JOIN $catalog_product_entity_int pei on pei.entity_id = sfoi.product_id
                        LEFT JOIN $eav_attribute ea ON pei.attribute_id = ea.attribute_id
                        LEFT JOIN $eav_attribute_option_value as eaov on pei.value = eaov.option_id
                        WHERE sfo.customer_id = e.entity_id AND ea.attribute_code = 'manufacturer' AND eaov.value is not null
                        GROUP BY eaov.value
                        HAVING count(*) > 0
                        ORDER BY count(*) DESC
                        LIMIT 1
                    )"
				),
			)
		);

//		$customerCollection->getSelect()
//		                   ->joinLeft(array($alias => $subselect), "{$alias}.s_customer_id = e.entity_id");


		return $customerCollection;
	}
}