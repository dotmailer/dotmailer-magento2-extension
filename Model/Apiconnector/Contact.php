<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Contact
{
	private $_start;
	private $_countCustomers = 0;
	private $_sqlExecuted = false;

	protected $_helper;
	protected $_registry;
	protected $_messageManager;
	protected $_storeManager;
	protected $_scopeConfig;
	protected $_contactFactory;
	protected $_contactCollection;
	protected $_resource;
	protected $_subscriberFactory;
	protected $_customerCollection;
	protected $_emailCustomer;
	protected $_proccessorFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\ProccessorFactory $proccessorFactory,
		\Dotdigitalgroup\Email\Model\Apiconnector\CustomerFactory $customerFactory,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\App\Resource $resource,
		\Dotdigitalgroup\Email\Helper\File   $file,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Dotdigitalgroup\Email\Helper\Config $config,
		\Magento\Backend\App\Action\Context $context,
		\Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
		\Magento\Customer\Model\Resource\Customer\CollectionFactory $customerCollectionFactory,
		\Dotdigitalgroup\Email\Model\Resource\Contact\CollectionFactory $contactCollectionFactory
	)
	{
		$this->_proccessorFactory = $proccessorFactory;
		$this->_file = $file;
		$this->_config = $config;
		$this->_helper = $helper;
		$this->_registry = $registry;
		$this->_resource = $resource;
		$this->_scopeConfig = $scopeConfig;
		$this->_storeManager = $storeManagerInterface;
		$this->_messageManager = $context->getMessageManager();
		//email contact
		$this->_emailCustomer = $customerFactory;
		$this->_contactFactory = $contactFactory;
		$this->_customerCollection = $customerCollectionFactory->create();
		$this->_customerCollection->addAttributeToSelect('*');
		//email contact collection
		$this->_contactCollection = $contactCollectionFactory->create();
		$this->_contactCollection->addFieldToSelect('*');
		//newsletter subscriber
		$this->_subscriberFactory = $subscriberFactory->create();
	}
	/**
	 * Contact sync.
	 *
	 * @return array
	 */
	public function sync()
	{
		//result message
		$result = array('success' => true, 'message' => '');
		//starting time for sync
		$this->_start = microtime(true);
		//resourse allocation
		$this->_helper->allowResourceFullExecution();
		$started = false;
		//export bulk contacts
		foreach ( $this->_helper->getWebsites() as $website ) {
			$apiEnabled             = $this->_helper->isEnabled( $website );
			$customerSyncEnabled    = $this->_helper->getCustomerSyncEnabled( $website );
			$customerAddressBook    = $this->_helper->getCustomerAddressBook($website);

			//api, customer sync and customer address book must be enabled
			if ($apiEnabled && $customerSyncEnabled && $customerAddressBook ) {
				//start log
				$contactsUpdated = $this->exportCustomersForWebsite($website);

				if ($this->_countCustomers && !$started) {
					$this->_helper->log( '---------- Start customer sync ----------' );
					$started = true;
				}
				// show message for any number of customers
				if ($contactsUpdated)
					$result['message'] .=  '</br>' . $website->getName() . ', exported contacts : ' . $contactsUpdated;
			}
		}
		//sync proccessed
		if ($this->_countCustomers) {
			$message = 'Total time for sync : ' . gmdate( "H:i:s", microtime( true ) - $this->_start ) . ', Total contacts : ' . $this->_countCustomers;
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
		$allMappedHash = array();
		//admin sync limit of batch size for contacts
		$syncLimit              = $this->_helper->getSyncLimit($website);
		//address book id mapped
		$customerAddressBook    = $this->_helper->getCustomerAddressBook($website);

		//skip website if address book not mapped
		if (! $customerAddressBook)
			return 0;

		$connection = $this->_resource->getConnection();
		$contactTable = $this->_resource->getTableName('email_contact');
		$select = $connection->select();
		//contacts ready for website
		$contacts = $this->_contactCollection
			->addFieldToFilter('email_imported', array('null' => true))
			->addFieldToFilter('customer_id', array('neq' => '0'))
			->addFieldToFilter('website_id', $website->getId())
			->setPageSize($syncLimit);

		// no contacts found
		if (!$contacts->getSize())
			return 0;
		//customer filename
		$customersFile = strtolower($website->getCode() . '_customers_' . date('d_m_Y_Hi') . '.csv');
		$this->_helper->log('Customers file : ' . $customersFile);
		//get customers ids
		$customerIds = $contacts->getColumnValues('customer_id');
		/**
		 * HEADERS.
		 */
		$mappedHash = $this->_helper->getWebsiteCustomerMappingDatafields($website);
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
			$connection->query($updateSql);
			//update is_subscriber column if subscriber_status is not null
			$connection->update($contactTable, array('is_subscriber' => 1), "subscriber_status is not null");

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
			$connection->query($deleteSql);

			//set flag
			$this->_sqlExecuted = true;
		}
		//customer collection
		$customerCollection = $this->_getCustomerCollection($customerIds, $website->getId());
		$countIds = array();
		foreach ($customerCollection as $customer) {
			$connectorCustomer = $this->_emailCustomer->create();
			$connectorCustomer->setMappingHash($mappedHash);
			$connectorCustomer->setCustomerData($customer);
			//count number of customers
			$countIds[] = $customer->getId();

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

		$customerNum = count($customerIds);
		$this->_helper->log('Website : ' . $website->getName() . ', customers = ' . $customerNum);
		$this->_helper->log('---------------------------- execution time :' . gmdate("H:i:s", microtime(true) - $this->_start));
		//file was created - continue for queue the export
		if (is_file($this->_file->getFilePath($customersFile))) {
			if ($customerNum > 0) {
				//register in queue with importer
				$this->_proccessorFactory->create()
					->registerQueue(
						\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_CONTACT,
						'',
						\Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
						$website->getId(),
						$customersFile
					);
				//set imported

				$tableName = $this->_resource->getTableName('email_contact');
				$ids = implode(', ', $customerIds);
				$connection->update($tableName, array('email_imported' => 1), "customer_id IN ($ids)");

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
		if ($contactId) {
			$contact = $this->_contactFactory->create()
				->load( $contactId );
		} else {
			$contact = $this->_registry->registry('current_contact');
		}
		if (! $contact->getId()) {
			$this->_messageManager->addError('No contact found!');
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
		$customerId = $contact->getCustomerId();
		if (!$customerId) {
			$this->_messageManager->addError('Cannot manually sync guests!');
			return false;
		}
		$client = $this->_helper->getWebsiteApiClient($website);

		//create customer filename
		$customersFile = strtolower($website->getCode() . '_customers_' . date('d_m_Y_Hi') . '.csv');
		$this->_helper->log('Customers file : ' . $customersFile);

		/**
		 * HEADERS.
		 */
		$mappedHash = $this->_helper->getWebsiteCustomerMappingDatafields($website);
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
		$customerCollection = $this->_getCustomerCollection(array($customerId), $website->getId());

		foreach ($customerCollection as $customer) {

			$contactModel = $this->_contactFactory->create()
				->loadByCustomerEmail($customer->getEmail(), $websiteId);
			//contact with this email not found
			if (!$contactModel->getId())
				continue;
			/**
			 * DATA.
			 */
			$connectorCustomer = $this->_customerFactory->create()
				->setMappingHash($mappedHash)
				->setCustomerData($customer);

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
			$subscriber = $this->_subscriberFactory->loadByEmail($customer->getEmail());
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
				$this->_proccessorFactory->create()
					->registerQueue(
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


	/**
	 * Customer collection with all data ready for export.
	 *
	 * @param $customerIds
	 * @param int $websiteId
	 *
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function _getCustomerCollection($customerIds, $websiteId = 0)
	{
		$customerCollection = $this->_customerCollection->addNameToSelect()
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

		$quote                          = $this->_resource->getTableName('quote');
		$sales_order                    = $this->_resource->getTableName('sales_order');
		$customer_log                   = $this->_resource->getTableName('customer_log');
		$eav_attribute                  = $this->_resource->getTableName('eav_attribute');
		$sales_order_grid               = $this->_resource->getTableName('sales_order_grid');
		$sales_order_item               = $this->_resource->getTableName('sales_order_item');
		$catalog_category_product_index = $this->_resource->getTableName('catalog_category_product');
		$eav_attribute_option_value     = $this->_resource->getTableName('eav_attribute_option_value');
		$catalog_product_entity_int     = $this->_resource->getTableName('catalog_product_entity_int');

		// get the last login date from the log_customer table
		$customerCollection->getSelect()->columns(
			array('last_logged_date' => new \Zend_Db_Expr ("(SELECT last_login_at FROM  $customer_log WHERE customer_id =e.entity_id ORDER BY log_id DESC LIMIT 1)")));

		// customer order information
		$alias = 'subselect';
		$statuses = $this->_helper->getWebsiteConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS, $websiteId
		);
		$statuses = explode(',', $statuses);

		$orderTable = $this->_resource->getTableName('sales_order');
		$connection = $this->_resource->getConnection();
		$subselect = $connection->select()
             ->from($orderTable, array(
	                 'customer_id as s_customer_id',
	                 'sum(grand_total) as total_spend',
	                 'count(*) as number_of_orders',
	                 'avg(grand_total) as average_order_value',
                 )
             )
             ->group('customer_id')
		;
		//any order statuses selected
		if ($statuses)
			$subselect->where("status in (?)", $statuses);

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

		$customerCollection->getSelect()
			->joinLeft(array($alias => $subselect), "{$alias}.s_customer_id = e.entity_id");

		return $customerCollection;
	}
}