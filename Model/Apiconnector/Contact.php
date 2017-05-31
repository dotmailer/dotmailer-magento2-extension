<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Contact
{
    /**
     * @var
     */
    public $start;
    /**
     * @var int
     */
    public $countCustomers = 0;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var
     */
    public $contactCollection;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;
    /**
     * @var
     */
    public $customerCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\Customer
     */
    public $emailCustomer;
    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $file;
    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\ContactImportQueueExport
     */
    public $contactImportQueueExport;

    /**
     * Contact constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\CustomerFactory $customerFactory
     * @param \Magento\Framework\App\ResourceConnection                        $resource
     * @param \Dotdigitalgroup\Email\Helper\File                               $file
     * @param \Dotdigitalgroup\Email\Helper\Data                               $helper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Apiconnector\CustomerFactory $customerFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory,
        \Dotdigitalgroup\Email\Model\Apiconnector\ContactImportQueueExport $contactImportQueueExport
    ) {
        $this->file            = $file;
        $this->helper          = $helper;
        $this->resource        = $resource;
        //email contact
        $this->emailCustomer      = $customerFactory;
        $this->customerCollection = $customerCollectionFactory;
        //email contact collection
        $this->contactCollection = $contactCollectionFactory;
        $this->contactImportQueueExport = $contactImportQueueExport;
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
        $this->start = microtime(true);
        //export bulk contacts
        foreach ($this->helper->getWebsites() as $website) {
            $apiEnabled = $this->helper->isEnabled($website);
            $customerSyncEnabled = $this->helper->isCustomerSyncEnabled(
                $website
            );
            $customerAddressBook = $this->helper->getCustomerAddressBook(
                $website
            );

            //api, customer sync and customer address book must be enabled
            if ($apiEnabled && $customerSyncEnabled && $customerAddressBook) {
                //start log
                $contactsUpdated = $this->exportCustomersForWebsite($website);

                // show message for any number of customers
                if ($contactsUpdated) {
                    $result['message'] .=  $website->getName()
                        . ', updated contacts ' . $contactsUpdated;
                }
            }
        }
        //sync proccessed
        if ($this->countCustomers) {
            $message = '----------- Customer sync ----------- : ' . gmdate('H:i:s', microtime(true) - $this->start) .
                ', Total contacts = ' . $this->countCustomers;
            $this->helper->log($message);
            $message .= $result['message'];
            $result['message'] = $message;
        }

        return $result;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     *
     * @return int
     */
    private function exportCustomersForWebsite(\Magento\Store\Api\Data\WebsiteInterface $website)
    {
        $allMappedHash = [];
        //admin sync limit of batch size for contacts
        $syncLimit = $this->helper->getSyncLimit($website);
        //address book id mapped
        $customerAddressBook = $this->helper->getCustomerAddressBook($website);

        //skip website if address book not mapped
        if (!$customerAddressBook) {
            return 0;
        }

        $connection = $this->resource->getConnection();

        //contacts ready for website
        $contacts = $this->getContactsReadyForWebsite($website, $syncLimit);

        // no contacts found
        if (!$contacts->getSize()) {
            return 0;
        }
        //customer filename
        $customersFile = strtolower(
            $website->getCode() . '_customers_' . date('d_m_Y_Hi') . '.csv'
        );
        $this->helper->log('Customers file : ' . $customersFile);
        //get customers ids
        $customerIds = $contacts->getColumnValues('customer_id');
        /*
         * HEADERS.
         */
        $mappedHash = $this->helper->getWebsiteCustomerMappingDatafields(
            $website
        );
        $headers = $mappedHash;

        //custom customer attributes
        $customAttributes = $this->helper->getCustomAttributes($website);

        if ($customAttributes) {
            foreach ($customAttributes as $data) {
                $headers[] = $data['datafield'];
                $allMappedHash[$data['attribute']] = $data['datafield'];
            }
        }
        $headers[] = 'Email';
        $headers[] = 'EmailType';

        $this->file->outputCSV(
            $this->file->getFilePath($customersFile),
            $headers
        );
        /*
         * END HEADERS.
         */

        //customer collection
        $customerCollection = $this->_getCustomerCollection(
            $customerIds,
            $website->getId()
        );

        $this->saveCsvFileDataForCustomers($customerCollection, $mappedHash, $customAttributes, $customersFile);

        $customerNum = count($customerIds);
        $this->helper->log(
            'Website : ' . $website->getName() . ', customers = ' . $customerNum .
            ', execution time :' . gmdate('H:i:s', microtime(true) - $this->start)
        );

        $this->queueExport($website, $customersFile, $customerNum, $customerIds, $connection);


        $this->countCustomers += $customerNum;

        return $customerNum;
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
    private function _getCustomerCollection($customerIds, $websiteId = 0)
    {
        $customerCollection = $this->buildCustomerCollection($customerIds);

        $quote = $this->resource->getTableName(
            'quote'
        );
        $salesOrder = $this->resource->getTableName(
            'sales_order'
        );
        $customerLog = $this->resource->getTableName(
            'customer_log'
        );
        $eavAttribute = $this->resource->getTableName(
            'eav_attribute'
        );
        $salesOrderGrid = $this->resource->getTableName(
            'sales_order_grid'
        );
        $salesOrderItem = $this->resource->getTableName(
            'sales_order_item'
        );
        $catalogCategoryProductIndex = $this->resource->getTableName(
            'catalog_category_product'
        );
        $eavAttributeOptionValue = $this->resource->getTableName(
            'eav_attribute_option_value'
        );
        $catalogProductEntityInt = $this->resource->getTableName(
            'catalog_product_entity_int'
        );

        // get the last login date from the log_customer table
        $customerCollection->getSelect()->columns([
            'last_logged_date' => new \Zend_Db_Expr(
                "(SELECT last_login_at FROM  $customerLog WHERE customer_id =e.entity_id ORDER BY log_id DESC LIMIT 1)"
            ),
            ]);

        // customer order information
        $alias = 'subselect';
        $statuses = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $websiteId
        );
        $statuses = explode(',', $statuses);

        $orderTable = $this->resource->getTableName('sales_order');
        $connection = $this->resource->getConnection();
        //@codingStandardsIgnoreStart
        $subselect = $connection->select()
            ->from(
                $orderTable, [
                    'customer_id as s_customer_id',
                    'sum(grand_total) as total_spend',
                    'count(*) as number_of_orders',
                    'avg(grand_total) as average_order_value',
                ]
            )
            ->group('customer_id');
        //any order statuses selected
        if ($statuses) {
            $subselect->where('status in (?)', $statuses);
        }

        $columnData = $this->buildColumnData($salesOrderGrid, $quote, $salesOrder, $salesOrderItem, $catalogCategoryProductIndex);
        $mostData = $this->buildMostData($salesOrder, $salesOrderItem, $catalogProductEntityInt, $eavAttribute, $eavAttributeOptionValue);


        $columnData['most_brand'] = $mostData;
        $customerCollection->getSelect()->columns(
            $columnData
        );

        $customerCollection->getSelect()
            ->joinLeft(
                [$alias => $subselect],
                "{$alias}.s_customer_id = e.entity_id"
            );
        //@codingStandardsIgnoreEnd

        return $customerCollection;
    }

    private function isRowIdExistsInCatalogProductEntityId()
    {
        $connection = $this->resource->getConnection();

        return  $connection->tableColumnExists($this->resource->getTableName('catalog_product_entity_int'), 'row_id');
    }

    /**
     * @param $salesOrderGrid
     * @param $quote
     * @param $salesOrder
     * @param $salesOrderItem
     * @param $catalogCategoryProductIndex
     * @return array
     */
    private function buildColumnData($salesOrderGrid, $quote, $salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        $columnData = [
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
            )
        ];
        return $columnData;
    }

    /**
     * @param $salesOrder
     * @param $salesOrderItem
     * @param $catalogProductEntityInt
     * @param $eavAttribute
     * @param $eavAttributeOptionValue
     * @return \Zend_Db_Expr
     */
    private function buildMostData($salesOrder, $salesOrderItem, $catalogProductEntityInt, $eavAttribute, $eavAttributeOptionValue)
    {
        /**
         * CatalogStaging fix.
         * @todo this will fix https://github.com/magento/magento2/issues/6478
         */
        $rowIdExists = $this->isRowIdExistsInCatalogProductEntityId();

        if ($rowIdExists) {
            $mostData = new \Zend_Db_Expr(
                "(
                    SELECT eaov.value from $salesOrder sfo
                    LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    LEFT JOIN $catalogProductEntityInt pei on pei.row_id = sfoi.product_id
                    LEFT JOIN $eavAttribute ea ON pei.attribute_id = ea.attribute_id
                    LEFT JOIN $eavAttributeOptionValue as eaov on pei.value = eaov.option_id
                    WHERE sfo.customer_id = e.entity_id AND ea.attribute_code = 'manufacturer' AND eaov.value is not null
                    GROUP BY eaov.value
                    HAVING count(*) > 0
                    ORDER BY count(*) DESC
                    LIMIT 1
                )"
            );
        } else {
            $mostData = new \Zend_Db_Expr(
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
            );

        }
        return $mostData;
    }

    /**
     * @param $customerIds
     * @return mixed
     */
    private function buildCustomerCollection($customerIds)
    {
        $customerCollection = $this->customerCollection->create()
            ->addAttributeToSelect('*')
            ->addNameToSelect();

        $customerCollection = $this->addBillingJoinAttributesToCustomerCollection($customerCollection);
        $customerCollection = $this->addShippingJoinAttributesToCustomerCollection($customerCollection);

        $customerCollection = $customerCollection->addAttributeToFilter('entity_id', ['in' => $customerIds]);
        return $customerCollection;
    }

    /**
     * @param $customerCollection
     * @param $mappedHash
     * @param $customAttributes
     * @param $customersFile
     */
    private function saveCsvFileDataForCustomers($customerCollection, $mappedHash, $customAttributes, $customersFile)
    {
        foreach ($customerCollection as $customer) {
            $connectorCustomer = $this->emailCustomer->create();
            $connectorCustomer->setMappingHash($mappedHash);
            $connectorCustomer->setCustomerData($customer);

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
            $this->file->outputCSV(
                $this->file->getFilePath($customersFile),
                $connectorCustomer->toCSVArray()
            );

            //clear collection and free memory
            $customer->clearInstance();
        }
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param $syncLimit
     * @return mixed
     */
    private function getContactsReadyForWebsite(\Magento\Store\Api\Data\WebsiteInterface $website, $syncLimit)
    {
        $contacts = $this->contactCollection->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('email_imported', ['null' => true])
            ->addFieldToFilter('customer_id', ['neq' => '0'])
            ->addFieldToFilter('website_id', $website->getId())
            ->setPageSize($syncLimit);
        return $contacts;
    }

    /**
     * @param $customerCollection
     * @return mixed
     */
    private function addShippingJoinAttributesToCustomerCollection($customerCollection)
    {
        $customerCollection = $customerCollection->joinAttribute(
            'shipping_street',
            'customer_address/street',
            'default_shipping',
            null,
            'left'
        )
            ->joinAttribute(
                'shipping_city',
                'customer_address/city',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_country_code',
                'customer_address/country_id',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_postcode',
                'customer_address/postcode',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_telephone',
                'customer_address/telephone',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_region',
                'customer_address/region',
                'default_shipping',
                null,
                'left'
            )
            ->joinAttribute(
                'shipping_company',
                'customer_address/company',
                'default_shipping',
                null,
                'left'
            );
        return $customerCollection;
    }

    /**
     * @param $customerCollection
     * @return mixed
     */
    private function addBillingJoinAttributesToCustomerCollection($customerCollection)
    {
        $customerCollection = $customerCollection
            ->joinAttribute(
                'billing_street',
                'customer_address/street',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_city',
                'customer_address/city',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_country_code',
                'customer_address/country_id',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_postcode',
                'customer_address/postcode',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_telephone',
                'customer_address/telephone',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_region',
                'customer_address/region',
                'default_billing',
                null,
                'left'
            )
            ->joinAttribute(
                'billing_company',
                'customer_address/company',
                'default_billing',
                null,
                'left'
            );
        return $customerCollection;
    }
}
