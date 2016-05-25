<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Contact
{

    /**
     * @var
     */
    protected $_start;
    /**
     * @var int
     */
    protected $_countCustomers = 0;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var
     */
    protected $_contactCollection;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    /**
     * @var
     */
    protected $_subscriberFactory;
    /**
     * @var
     */
    protected $_customerCollection;
    /**
     * @var CustomerFactory
     */
    protected $_emailCustomer;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

    /**
     * Contact constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory                     $importerFactory
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\CustomerFactory        $customerFactory
     * @param \Magento\Framework\Registry                                      $registry
     * @param \Magento\Framework\App\ResourceConnection                        $resource
     * @param \Dotdigitalgroup\Email\Helper\File                               $file
     * @param \Dotdigitalgroup\Email\Helper\Data                               $helper
     * @param \Dotdigitalgroup\Email\Helper\Config                             $config
     * @param \Magento\Backend\App\Action\Context                              $context
     * @param \Magento\Newsletter\Model\SubscriberFactory                      $subscriberFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface               $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface                       $storeManagerInterface
     * @param \Dotdigitalgroup\Email\Model\ContactFactory                      $contactFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\Resource\Contact\CollectionFactory  $contactCollectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\Apiconnector\CustomerFactory $customerFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ResourceConnection $resource,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Config $config,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Dotdigitalgroup\Email\Model\Resource\Contact\CollectionFactory $contactCollectionFactory
    ) {
        $this->_importerFactory = $importerFactory;
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
        $result = ['success' => true, 'message' => ''];
        //starting time for sync
        $this->_start = microtime(true);
        //resourse allocation
        $this->_helper->allowResourceFullExecution();
        $started = false;
        //export bulk contacts
        foreach ($this->_helper->getWebsites() as $website) {
            $apiEnabled = $this->_helper->isEnabled($website);
            $customerSyncEnabled = $this->_helper->isCustomerSyncEnabled(
                $website
            );
            $customerAddressBook = $this->_helper->getCustomerAddressBook(
                $website
            );

            //api, customer sync and customer address book must be enabled
            if ($apiEnabled && $customerSyncEnabled && $customerAddressBook) {
                //start log
                $contactsUpdated = $this->exportCustomersForWebsite($website);

                if ($this->_countCustomers && !$started) {
                    $this->_helper->log(
                        '---------- Start customer sync ----------'
                    );
                    $started = true;
                }
                // show message for any number of customers
                if ($contactsUpdated) {
                    $result['message'] .= '</br>'.$website->getName()
                        .', exported contacts : '.$contactsUpdated;
                }
            }
        }
        //sync proccessed
        if ($this->_countCustomers) {
            $message = 'Total time for sync : '.gmdate(
                    'H:i:s', microtime(true) - $this->_start
                ).', Total contacts : '.$this->_countCustomers;
            $this->_helper->log($message);
            $message .= $result['message'];
            $result['message'] = $message;
        }

        return $result;
    }

    /**
     * * Execute the contact sync for the website.
     *
     * @param \Magento\Store\Model\Website $website
     *
     * @return int
     */
    public function exportCustomersForWebsite(\Magento\Store\Model\Website $website)
    {
        $allMappedHash = [];
        //admin sync limit of batch size for contacts
        $syncLimit = $this->_helper->getSyncLimit($website);
        //address book id mapped
        $customerAddressBook = $this->_helper->getCustomerAddressBook($website);

        //skip website if address book not mapped
        if (!$customerAddressBook) {
            return 0;
        }

        $connection = $this->_resource->getConnection();

        //contacts ready for website
        $contacts = $this->_contactCollection
            ->addFieldToFilter('email_imported', ['null' => true])
            ->addFieldToFilter('customer_id', ['neq' => '0'])
            ->addFieldToFilter('website_id', $website->getId())
            ->setPageSize($syncLimit);

        // no contacts found
        if (!$contacts->getSize()) {
            return 0;
        }
        //customer filename
        $customersFile = strtolower(
            $website->getCode().'_customers_'.date('d_m_Y_Hi').'.csv'
        );
        $this->_helper->log('Customers file : '.$customersFile);
        //get customers ids
        $customerIds = $contacts->getColumnValues('customer_id');
        /*
         * HEADERS.
         */
        $mappedHash = $this->_helper->getWebsiteCustomerMappingDatafields(
            $website
        );
        $headers = $mappedHash;

        //custom customer attributes
        $customAttributes = $this->_helper->getCustomAttributes($website);

        if ($customAttributes) {
            foreach ($customAttributes as $data) {
                $headers[] = $data['datafield'];
                $allMappedHash[$data['attribute']] = $data['datafield'];
            }
        }
        $headers[] = 'Email';
        $headers[] = 'EmailType';

        $this->_file->outputCSV(
            $this->_file->getFilePath($customersFile), $headers
        );
        /*
         * END HEADERS.
         */

        //customer collection
        $customerCollection = $this->_getCustomerCollection(
            $customerIds, $website->getId()
        );
        $countIds = [];
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
            $this->_file->outputCSV(
                $this->_file->getFilePath($customersFile),
                $connectorCustomer->toCSVArray()
            );

            //clear collection and free memory
            $customer->clearInstance();
        }

        $customerNum = count($customerIds);
        $this->_helper->log(
            'Website : '.$website->getName().', customers = '.$customerNum
        );
        $this->_helper->log(
            '---------------------------- execution time :'.gmdate(
                'H:i:s', microtime(true) - $this->_start
            )
        );
        //file was created - continue for queue the export
        if (is_file($this->_file->getFilePath($customersFile))) {
            if ($customerNum > 0) {
                //register in queue with importer
                $this->_importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT,
                        '',
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId(),
                        $customersFile
                    );
                //set imported

                $tableName = $this->_resource->getTableName('email_contact');
                $ids = implode(', ', $customerIds);
                $connection->update(
                    $tableName, ['email_imported' => 1],
                    "customer_id IN ($ids)"
                );
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
                ->load($contactId);
        } else {
            $contact = $this->_registry->registry('current_contact');
        }
        if (!$contact->getId()) {
            $this->_messageManager->addError('No contact found!');

            return false;
        }

        $websiteId = $contact->getWebsiteId();
        $website = $this->_storeManager->getWebsite($websiteId);
        $updated = 0;
        $customers = $headers = $allMappedHash = [];
        $this->_helper->log('---------- Start single customer sync ----------');
        //skip if the mapping field is missing
        if (!$this->_helper->getCustomerAddressBook($website)) {
            return false;
        }
        $customerId = $contact->getCustomerId();
        if (!$customerId) {
            $this->_messageManager->addError('Cannot manually sync guests!');

            return false;
        }
        $client = $this->_helper->getWebsiteApiClient($website);

        //create customer filename
        $customersFile = strtolower(
            $website->getCode().'_customers_'.date('d_m_Y_Hi').'.csv'
        );
        $this->_helper->log('Customers file : '.$customersFile);

        /*
         * HEADERS.
         */
        $mappedHash = $this->_helper->getWebsiteCustomerMappingDatafields(
            $website
        );
        $headers = $mappedHash;
        //custom customer attributes
        $customAttributes = $this->_helper->getCustomAttributes($website);
        foreach ($customAttributes as $data) {
            $headers[] = $data['datafield'];
            $allMappedHash[$data['attribute']] = $data['datafield'];
        }
        $headers[] = 'Email';
        $headers[] = 'EmailType';
        $this->_file->outputCSV(
            $this->_file->getFilePath($customersFile), $headers
        );
        /*
         * END HEADERS.
         */
        $customerCollection = $this->_getCustomerCollection(
            [$customerId], $website->getId()
        );

        foreach ($customerCollection as $customer) {
            $contactModel = $this->_contactFactory->create()
                ->loadByCustomerEmail($customer->getEmail(), $websiteId);
            //contact with this email not found
            if (!$contactModel->getId()) {
                continue;
            }
            /*
             * DATA.
             */
            $connectorCustomer = $this->_emailCustomer->create()
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
            $this->_file->outputCSV(
                $this->_file->getFilePath($customersFile),
                $connectorCustomer->toCSVArray()
            );

            /*
             * END DATA.
             */

            //mark the contact as imported
            $contactModel->setEmailImported(
                \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED
            );
            $subscriber = $this->_subscriberFactory->loadByEmail(
                $customer->getEmail()
            );
            if ($subscriber->isSubscribed()) {
                $contactModel->setIsSubscriber('1')
                    ->setSubscriberStatus($subscriber->getSubscriberStatus());
            }

            $contactModel->save();
            ++$updated;
        }

        if (is_file($this->_file->getFilePath($customersFile))) {
            //import contacts
            if ($updated > 0) {
                //register in queue with importer
                $this->_importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT,
                        '',
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId(),
                        $customersFile
                    );
                $client->postAddressBookContactsImport(
                    $customersFile,
                    $this->_helper->getCustomerAddressBook($website)
                );
            }
        }

        return $contact->getEmail();
    }

    /**
     * Customer collection with all data ready for export.
     *
     * @param     $customerIds
     * @param int $websiteId
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getCustomerCollection($customerIds, $websiteId = 0)
    {
        $customerCollection = $this->_customerCollection->addNameToSelect()
            ->joinAttribute(
                'billing_street', 'customer_address/street', 'default_billing',
                null, 'left'
            )
            ->joinAttribute(
                'billing_city', 'customer_address/city', 'default_billing',
                null, 'left'
            )
            ->joinAttribute(
                'billing_country_code', 'customer_address/country_id',
                'default_billing', null, 'left'
            )
            ->joinAttribute(
                'billing_postcode', 'customer_address/postcode',
                'default_billing', null, 'left'
            )
            ->joinAttribute(
                'billing_telephone', 'customer_address/telephone',
                'default_billing', null, 'left'
            )
            ->joinAttribute(
                'billing_region', 'customer_address/region', 'default_billing',
                null, 'left'
            )
            ->joinAttribute(
                'billing_company', 'customer_address/company', 'default_billing',
                null, 'left'
            )
            ->joinAttribute(
                'shipping_street', 'customer_address/street',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_city', 'customer_address/city', 'default_shipping',
                null, 'left'
            )
            ->joinAttribute(
                'shipping_country_code', 'customer_address/country_id',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_postcode', 'customer_address/postcode',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_telephone', 'customer_address/telephone',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_region', 'customer_address/region',
                'default_shipping', null, 'left'
            )
            ->joinAttribute(
                'shipping_company', 'customer_address/company',
                'default_shipping', null, 'left'
            )
            ->addAttributeToFilter('entity_id', ['in' => $customerIds]);

        $quote = $this->_resource->getTableName(
            'quote'
        );
        $salesOrder = $this->_resource->getTableName(
            'sales_order'
        );
        $customerLog = $this->_resource->getTableName(
            'customer_log'
        );
        $eavAttribute = $this->_resource->getTableName(
            'eav_attribute'
        );
        $salesOrderGrid = $this->_resource->getTableName(
            'sales_order_grid'
        );
        $salesOrderItem = $this->_resource->getTableName(
            'sales_order_item'
        );
        $catalogCategoryProductIndex = $this->_resource->getTableName(
            'catalog_category_product'
        );
        $eavAttributeOptionValue = $this->_resource->getTableName(
            'eav_attribute_option_value'
        );
        $catalogProductEntityInt = $this->_resource->getTableName(
            'catalog_product_entity_int'
        );

        // get the last login date from the log_customer table
        $customerCollection->getSelect()->columns(
            [
                'last_logged_date' => new \Zend_Db_Expr(
                    "(SELECT last_login_at FROM  $customerLog WHERE customer_id =e.entity_id ORDER BY log_id DESC LIMIT 1)"
                )
            ]
        );

        // customer order information
        $alias = 'subselect';
        $statuses = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $websiteId
        );
        $statuses = explode(',', $statuses);

        $orderTable = $this->_resource->getTableName('sales_order');
        $connection = $this->_resource->getConnection();
        $subselect = $connection->select()
            ->from(
                $orderTable, [
                    'customer_id as s_customer_id',
                    'sum(grand_total) as total_spend',
                    'count(*) as number_of_orders',
                    'avg(grand_total) as average_order_value'
                ]
            )
            ->group('customer_id');
        //any order statuses selected
        if ($statuses) {
            $subselect->where('status in (?)', $statuses);
        }

        $customerCollection->getSelect()->columns(
            [
                'last_order_date' => new \Zend_Db_Expr(
                    "(SELECT created_at FROM $salesOrderGrid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"
                ),
                'last_order_id' => new \Zend_Db_Expr(
                    "(SELECT entity_id FROM $salesOrderGrid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"
                ),
                'last_increment_id' => new \Zend_Db_Expr(
                    "(SELECT increment_id FROM $salesOrderGrid WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1)"
                ),
                'last_quote_id' => new \Zend_Db_Expr(
                    "(SELECT entity_id FROM $quote WHERE customer_id = e.entity_id ORDER BY created_at DESC LIMIT 1)"
                ),
                'first_category_id' => new \Zend_Db_Expr(
                    "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
                ),
                'last_category_id' => new \Zend_Db_Expr(
                    "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
                ),
                'product_id_for_first_brand' => new \Zend_Db_Expr(
                    "(
                        SELECT sfoi.product_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_id = e.entity_id and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
                ),
                'product_id_for_last_brand' => new \Zend_Db_Expr(
                    "(
                        SELECT sfoi.product_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_id = e.entity_id and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
                ),
                'week_day' => new \Zend_Db_Expr(
                    "(
                        SELECT dayname(created_at) as week_day
                        FROM $salesOrder
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
                        FROM $salesOrder
                        WHERE customer_id = e.entity_id
                        GROUP BY month_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
                ),
                'most_category_id' => new \Zend_Db_Expr(
                    "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        LEFT JOIN $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_id = e.entity_id AND ccpi.category_id is not null
                        GROUP BY category_id
                        HAVING COUNT(sfoi.product_id) > 0
                        ORDER BY COUNT(sfoi.product_id) DESC
                        LIMIT 1
                    )"
                ),
                'most_brand' => new \Zend_Db_Expr(
                    "(
                        SELECT eaov.value from $salesOrder sfo
                        LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        LEFT JOIN $catalogProductEntityInt pei on pei.entity_id = sfoi.product_id
                        LEFT JOIN $eavAttribute ea ON pei.attribute_id = ea.attribute_id
                        LEFT JOIN $eavAttributeOptionValue as eaov on pei.value = eaov.option_id
                        WHERE sfo.customer_id = e.entity_id AND ea.attribute_code = 'manufacturer' AND eaov.value is not null
                        GROUP BY eaov.value
                        HAVING count(*) > 0
                        ORDER BY count(*) DESC
                        LIMIT 1
                    )"
                ),
            ]
        );

        $customerCollection->getSelect()
            ->joinLeft(
                [$alias => $subselect],
                "{$alias}.s_customer_id = e.entity_id"
            );

        return $customerCollection;
    }
}
