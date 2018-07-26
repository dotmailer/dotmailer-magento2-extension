<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    public $subscribersCollection;

    /**
     * @var Contact\CollectionFactory
     */
    public $contactCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $customerCollection;

    /**
     * @var \Magento\Cron\Model\ScheduleFactory
     */
    private $schelduleFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sql\ExpressionFactory
     */
    private $expressionFactory;

    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init('email_contact', 'email_contact_id');
    }

    /**
     * Contact constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Cron\Model\ScheduleFactory $schedule
     * @param \Dotdigitalgroup\Email\Model\Sql\ExpressionFactory $expressionFactory
     * @param Contact\CollectionFactory $contactCollectionFactory
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Cron\Model\ScheduleFactory $schedule,
        \Dotdigitalgroup\Email\Model\Sql\ExpressionFactory $expressionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory,
        $connectionName = null
    ) {
        $this->expressionFactory = $expressionFactory;
        $this->schelduleFactory = $schedule;
        $this->customerCollection = $customerCollectionFactory;
        $this->subscribersCollection = $subscriberCollection;
        $this->contactCollectionFactory = $contactCollectionFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * Remove all contact_id from the table.
     *
     * @return int
     *
     */
    public function deleteContactIds()
    {
        $conn = $this->getConnection();
        $num = $conn->update(
            $this->getTable('email_contact'),
            ['contact_id' => $this->expressionFactory->create(["expression" => 'null'])],
            $conn->quoteInto(
                'contact_id is ?',
                $this->expressionFactory->create(["expression" => 'not null'])
            )
        );

        return $num;
    }

    /**
     * Reset the imported contacts.
     *
     * @return int
     *
     */
    public function resetAllContacts()
    {
        $conn = $this->getConnection();
        $num = $conn->update(
            $this->getTable('email_contact'),
            ['email_imported' => $this->expressionFactory->create(["expression" => 'null'])],
            $conn->quoteInto(
                'email_imported is ?',
                $this->expressionFactory->create(["expression" => 'not null'])
            )
        );

        return $num;
    }

    /**
     * Set all imported subscribers for reimport.
     *
     * @return int
     *
     */
    public function resetSubscribers()
    {
        $conn = $this->getConnection();

        $num = $conn->update(
            $this->getTable('email_contact'),
            ['subscriber_imported' => $this->expressionFactory->create(["expression" => 'null'])],
            $conn->quoteInto(
                'subscriber_imported is ?',
                $this->expressionFactory->create(["expression" => 'not null'])
            )
        );

        return $num;
    }

    /**
     * Unsubscribe a contact from email_contact/newsletter table.
     *
     * @param array $emails
     * @return int
     */
    public function unsubscribe($emails)
    {
        if (! empty($emails) && is_array($emails)) {
            $write = $this->getConnection();

            //un-subscribe from the email contact table.
            $updated = $write->update(
                $this->getMainTable(),
                [
                    'is_subscriber' => $this->expressionFactory->create(["expression" => 'null']),
                    'subscriber_status' => \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED,
                    'suppressed' => '1',
                ],
                ["email IN (?)" => $emails]
            );

            // un-subscribe newsletter subscribers
            $write->update(
                $this->getTable('newsletter_subscriber'),
                ['subscriber_status' => \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED],
                ["subscriber_email IN (?)" => $emails]
            );

            return $updated;
        }

        return 0;
    }

    /**
     * email, website_id, store_id, is_guest
     * @param array $guests
     *
     * @return null
     */
    public function insertGuests($guests)
    {
        $write = $this->getConnection();
        if (! empty($guests)) {
            $write->insertMultiple($this->getMainTable(), $guests);
        }
    }

    /**
     * @param array $guests
     */
    public function updateContactsAsGuests($guests)
    {
        $write = $this->getConnection();
        if (! empty($guests)) {
            //make sure the contact are marked as guests if already exists
            $where = ['email IN (?)' => $guests, 'is_guest IS NULL'];
            $data = ['is_guest' => 1];
            $write->update($this->getMainTable(), $data, $where);
        }
    }

    /**
     * Set suppressed for contact ids.
     *
     * @param array $suppressedContactIds
     *
     * @return int
     */
    public function setContactSuppressedForContactIds($suppressedContactIds)
    {
        if (empty($suppressedContactIds)) {
            return 0;
        }
        $conn = $this->getConnection();
        //update suppressed for contacts
        $updated = $conn->update(
            $this->getMainTable(),
            ['suppressed' => 1],
            ['email_contact_id IN(?)' => $suppressedContactIds]
        );

        return $updated;
    }

    /**
     * Update subscriber imported.
     *
     * @param array $emailContactIds
     * @return int
     */
    public function updateSubscribers($emailContactIds)
    {
        if (empty($emailContactIds)) {
            return 0;
        }
        $write = $this->getConnection();
        //update subscribers imported
        $updated = $write->update(
            $this->getMainTable(),
            ['subscriber_imported' => 1],
            ["email_contact_id IN (?)" => $emailContactIds]
        );

        return $updated;
    }

    /**
     * Get collection for subscribers by emails.
     *
     * @param array $emails
     * @param array $orderStatuses
     * @param string|boolean $brand
     *
     * @return \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    public function getContactSubscribersWithOrderStatusesAndBrand($emails, $orderStatuses, $brand)
    {
        $salesOrder = $this->getTable('sales_order');
        $salesOrderItem = $this->getTable('sales_order_item');
        $catalogProductEntityInt = $this->getTable('catalog_product_entity_int');
        $eavAttribute = $this->getTable('eav_attribute');
        $eavAttributeOptionValue = $this->getTable('eav_attribute_option_value');
        $catalogCategoryProductIndex = $this->getTable('catalog_category_product');

        $contactCollection = $this->contactCollectionFactory->create()
            ->addFieldToSelect([
                'email',
                'store_id',
                'subscriber_status'
            ]);

        //only when subscriber emails are set
        if (! empty($emails)) {
            $contactCollection->addFieldToFilter('email', $emails);
        }

        $alias = 'subselect';
        $subselect = $this->getConnection()->select()
            ->from(
                $salesOrder,
                [
                    'customer_email as s_customer_email',
                    'sum(grand_total) as total_spend',
                    'count(*) as number_of_orders',
                    'avg(grand_total) as average_order_value',
                ]
            )
            ->group('customer_email');
        //any order statuses selected
        if (! empty($orderStatuses)) {
            $subselect->where('status in (?)', $orderStatuses);
        }

        $columns = $this->buildCollectionColumns($salesOrder, $salesOrderItem, $catalogCategoryProductIndex);

        $mostData = $this->buildMostData(
            $salesOrder,
            $salesOrderItem,
            $catalogProductEntityInt,
            $eavAttribute,
            $eavAttributeOptionValue,
            $brand,
            true
        );

        $columns['most_brand'] = $mostData;
        $contactCollection->getSelect()->columns($columns);

        $contactCollection->getSelect()
            ->joinLeft(
                [$alias => $subselect],
                "{$alias}.s_customer_email = main_table.email"
            );

        return $contactCollection;
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $catalogCategoryProductIndex
     *
     * @return array
     */
    private function buildCollectionColumns($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        $columns = [
            'last_order_date' => $this->createLastOrderDataColumn($salesOrder),
            'last_order_id' => $this->createLastOrderIdColumn($salesOrder),
            'last_increment_id' => $this->createLastIncrementIdColumn($salesOrder),
            'first_category_id' => $this->createFirstCategoryIdColumn(
                $salesOrder,
                $salesOrderItem,
                $catalogCategoryProductIndex
            ),
            'last_category_id' => $this->createLastCategoryIdColumn(
                $salesOrder,
                $salesOrderItem,
                $catalogCategoryProductIndex
            ),
            'product_id_for_first_brand' => $this->createProductIdForFirstBrandColumn($salesOrder, $salesOrderItem),
            'product_id_for_last_brand' => $this->createProductIdForLastBrandColumn($salesOrder, $salesOrderItem),
            'week_day' => $this->createWeekDayColumn($salesOrder),
            'month_day' => $this->createMonthDayColumn($salesOrder),
            'most_category_id' => $this->createMostCategoryIdColumn(
                $salesOrder,
                $salesOrderItem,
                $catalogCategoryProductIndex
            )
        ];

        return $columns;
    }

    /**
     * @param string $salesOrder
     *
     * @return \Zend_Db_Expr
     */
    private function createLastOrderDataColumn($salesOrder)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT created_at FROM $salesOrder 
                WHERE customer_email = main_table.email 
                ORDER BY created_at DESC 
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     *
     * @return \Zend_Db_Expr
     */
    private function createLastOrderIdColumn($salesOrder)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT entity_id FROM $salesOrder
                WHERE customer_email = main_table.email 
                ORDER BY created_at DESC 
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     *
     * @return \Zend_Db_Expr
     */
    private function createLastIncrementIdColumn($salesOrder)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT increment_id FROM $salesOrder
                WHERE customer_email = main_table.email 
                ORDER BY created_at DESC 
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $catalogCategoryProductIndex
     *
     * @return \Zend_Db_Expr
     */
    private function createFirstCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT ccpi.category_id FROM $salesOrder as sfo
                left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                WHERE sfo.customer_email = main_table.email
                ORDER BY sfo.created_at ASC, sfoi.price DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $catalogCategoryProductIndex
     *
     * @return \Zend_Db_Expr
     */
    private function createLastCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT ccpi.category_id FROM $salesOrder as sfo
                left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                WHERE sfo.customer_email = main_table.email
                ORDER BY sfo.created_at DESC, sfoi.price DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     *
     * @return \Zend_Db_Expr
     */
    private function createProductIdForFirstBrandColumn($salesOrder, $salesOrderItem)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT sfoi.product_id FROM $salesOrder as sfo
                left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                WHERE sfo.customer_email = main_table.email and sfoi.product_type = 'simple'
                ORDER BY sfo.created_at ASC, sfoi.price DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     *
     * @return \Zend_Db_Expr
     */
    private function createProductIdForLastBrandColumn($salesOrder, $salesOrderItem)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT sfoi.product_id FROM $salesOrder as sfo
                left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                WHERE sfo.customer_email = main_table.email and sfoi.product_type = 'simple'
                ORDER BY sfo.created_at DESC, sfoi.price DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     *
     * @return \Zend_Db_Expr
     */
    private function createWeekDayColumn($salesOrder)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT dayname(created_at) as week_day
                FROM $salesOrder
                WHERE customer_email = main_table.email
                GROUP BY week_day
                HAVING COUNT(*) > 0
                ORDER BY (COUNT(*)) DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     *
     * @return \Zend_Db_Expr
     */
    private function createMonthDayColumn($salesOrder)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT monthname(created_at) as month_day
                FROM $salesOrder
                WHERE customer_email = main_table.email
                GROUP BY month_day
                HAVING COUNT(*) > 0
                ORDER BY (COUNT(*)) DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $catalogCategoryProductIndex
     *
     * @return \Zend_Db_Expr
     */
    private function createMostCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT ccpi.category_id FROM $salesOrder as sfo
                LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                LEFT JOIN $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                WHERE sfo.customer_email = main_table.email AND ccpi.category_id is not null
                GROUP BY category_id
                HAVING COUNT(sfoi.product_id) > 0
                ORDER BY COUNT(sfoi.product_id) DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @return bool
     */
    private function isRowIdExistsInCatalogProductEntityId()
    {
        $connection = $this->getConnection();

        return $connection->tableColumnExists(
            $this->getTable('catalog_product_entity_int'),
            'row_id'
        );
    }

    /**
     * Customer collection with all data ready for export.
     *
     * @param  array $customerIds
     * @param  array $statuses
     * @param string|boolean $brand
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerCollectionByIds($customerIds, $statuses, $brand)
    {
        $customerCollection = $this->buildCustomerCollection($customerIds);

        $quote = $this->getTable(
            'quote'
        );
        $salesOrder = $this->getTable(
            'sales_order'
        );
        $customerLog = $this->getTable(
            'customer_log'
        );
        $eavAttribute = $this->getTable(
            'eav_attribute'
        );
        $salesOrderGrid = $this->getTable(
            'sales_order_grid'
        );
        $salesOrderItem = $this->getTable(
            'sales_order_item'
        );
        $catalogCategoryProductIndex = $this->getTable(
            'catalog_category_product'
        );
        $eavAttributeOptionValue = $this->getTable(
            'eav_attribute_option_value'
        );
        $catalogProductEntityInt = $this->getTable(
            'catalog_product_entity_int'
        );

        // get the last login date from the log_customer table
        $customerCollection->getSelect()->columns([
            'last_logged_date' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT last_login_at 
                    FROM  $customerLog 
                    WHERE customer_id =e.entity_id ORDER BY log_id DESC LIMIT 1
                )"]
            ),
        ]);

        // customer order information
        $alias = 'subselect';

        $orderTable = $this->getTable('sales_order');
        $connection = $this->getConnection();
        $subselect = $connection->select()
            ->from(
                $orderTable,
                [
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

        $columnData = $this->buildColumnData(
            $salesOrderGrid,
            $quote,
            $salesOrder,
            $salesOrderItem,
            $catalogCategoryProductIndex
        );
        $mostData = $this->buildMostData(
            $salesOrder,
            $salesOrderItem,
            $catalogProductEntityInt,
            $eavAttribute,
            $eavAttributeOptionValue,
            $brand
        );

        $columnData['most_brand'] = $mostData;
        $customerCollection->getSelect()->columns(
            $columnData
        );

        $customerCollection->getSelect()
            ->joinLeft(
                [$alias => $subselect],
                "{$alias}.s_customer_id = e.entity_id"
            );

        return $customerCollection;
    }

    /**
     * @param array $customerIds
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
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
     * @param string $salesOrderGrid
     * @param string $quote
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $catalogCategoryProductIndex
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function buildColumnData(
        $salesOrderGrid,
        $quote,
        $salesOrder,
        $salesOrderItem,
        $catalogCategoryProductIndex
    ) {
        $columnData = [
            'last_order_date' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT created_at
                    FROM $salesOrderGrid
                    WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1
                )"]
            ),
            'last_order_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT entity_id
                    FROM $salesOrderGrid
                    WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1
                )"]
            ),
            'last_increment_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT increment_id
                    FROM $salesOrderGrid
                    WHERE customer_id =e.entity_id ORDER BY created_at DESC LIMIT 1
                )"]
            ),
            'last_quote_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT entity_id
                    FROM $quote
                    WHERE customer_id = e.entity_id ORDER BY created_at DESC LIMIT 1
                )"]
            ),
            'first_category_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT ccpi.category_id FROM $salesOrder as sfo
                    left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                    WHERE sfo.customer_id = e.entity_id
                    ORDER BY sfo.created_at ASC, sfoi.price DESC
                    LIMIT 1
                )"]
            ),
            'last_category_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT ccpi.category_id FROM $salesOrder as sfo
                    left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                    WHERE sfo.customer_id = e.entity_id
                    ORDER BY sfo.created_at DESC, sfoi.price DESC
                    LIMIT 1
                )"]
            ),
            'product_id_for_first_brand' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT sfoi.product_id FROM $salesOrder as sfo
                    left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    WHERE sfo.customer_id = e.entity_id and sfoi.product_type = 'simple'
                    ORDER BY sfo.created_at ASC, sfoi.price DESC
                    LIMIT 1
                )"]
            ),
            'product_id_for_last_brand' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT sfoi.product_id FROM $salesOrder as sfo
                    left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    WHERE sfo.customer_id = e.entity_id and sfoi.product_type = 'simple'
                    ORDER BY sfo.created_at DESC, sfoi.price DESC
                    LIMIT 1
                )"]
            ),
            'week_day' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT dayname(created_at) as week_day
                    FROM $salesOrder
                    WHERE customer_id = e.entity_id
                    GROUP BY week_day
                    HAVING COUNT(*) > 0
                    ORDER BY (COUNT(*)) DESC
                    LIMIT 1
                )"]
            ),
            'month_day' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT monthname(created_at) as month_day
                    FROM $salesOrder
                    WHERE customer_id = e.entity_id
                    GROUP BY month_day
                    HAVING COUNT(*) > 0
                    ORDER BY (COUNT(*)) DESC
                    LIMIT 1
                )"]
            ),
            'most_category_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT ccpi.category_id FROM $salesOrder as sfo
                    LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    LEFT JOIN $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                    WHERE sfo.customer_id = e.entity_id AND ccpi.category_id is not null
                    GROUP BY category_id
                    HAVING COUNT(sfoi.product_id) > 0
                    ORDER BY COUNT(sfoi.product_id) DESC
                    LIMIT 1
                )"]
            )
        ];

        return $columnData;
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $catalogProductEntityInt
     * @param string $eavAttribute
     * @param string $eavAttributeOptionValue
     * @param string|boolean $brand
     * @param boolean $forSubscriber
     *
     * @return string|\Zend_Db_Expr
     */
    private function buildMostData(
        $salesOrder,
        $salesOrderItem,
        $catalogProductEntityInt,
        $eavAttribute,
        $eavAttributeOptionValue,
        $brand,
        $forSubscriber = false
    ) {
        if (! $brand) {
            return $this->expressionFactory->create(["expression" => 'NULL']);
        }

        $where = ($forSubscriber == true)?
            'WHERE sfo.customer_email = main_table.email' : 'WHERE sfo.customer_id = e.entity_id ';

        /**
         * CatalogStaging fix.
         * @todo this will fix https://github.com/magento/magento2/issues/6478
         */
        $leftJoinOnSfoiProductId = $this->isRowIdExistsInCatalogProductEntityId() ?
            'pei.row_id' : 'pei.entity_id';

        $brand = $this->getConnection()->quote($brand);

        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT eaov.option_id from $salesOrder sfo
                LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                LEFT JOIN $catalogProductEntityInt pei on $leftJoinOnSfoiProductId = sfoi.product_id
                LEFT JOIN $eavAttribute ea ON pei.attribute_id = ea.attribute_id
                LEFT JOIN $eavAttributeOptionValue as eaov on pei.value = eaov.option_id
                $where
                AND ea.attribute_code = $brand
                AND eaov.value is not null
                GROUP BY eaov.option_id
                HAVING count(*) > 0
                ORDER BY count(*) DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
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
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    private function addBillingJoinAttributesToCustomerCollection($customerCollection)
    {
        $customerCollection = $customerCollection->joinAttribute(
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

    /**
     * Set imported by id.
     *
     * @param array $ids
     *
     * @return null
     */
    public function setImportedByIds($ids)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['email_imported' => 1],
            ["customer_id IN (?)" => $ids]
        );
    }

    /**
     * Get last cron ran date.
     *
     * @param string $cronJob
     *
     * @return boolean|string
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
        $executedAt = $collection->getFirstItem()->getExecutedAt();

        return $executedAt;
    }

    /**
     * Update contacts to re-import by customer ids
     *
     * @param array $customerIds
     */
    public function updateNotImportedByCustomerIds($customerIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['email_imported' => $this->expressionFactory->create(["expression" => 'null'])],
            ["customer_id IN (?)" => $customerIds]
        );
    }
}
