<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Store\Api\Data\WebsiteInterface;

class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    public $subscribersCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory
     */
    public $contactCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $customerCollection;

    /**
     * @var \Magento\Cron\Model\ScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sql\ExpressionFactory
     */
    private $expressionFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\QuoteFactory
     */
    private $quoteResourceFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_CONTACT_TABLE, 'email_contact_id');
    }

    /**
     * Contact constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Cron\Model\ScheduleFactory $schedule
     * @param \Dotdigitalgroup\Email\Model\Sql\ExpressionFactory $expressionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Quote\Model\ResourceModel\QuoteFactory $quoteResourceFactory
     * @param Config $config
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Cron\Model\ScheduleFactory $schedule,
        \Dotdigitalgroup\Email\Model\Sql\ExpressionFactory $expressionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Quote\Model\ResourceModel\QuoteFactory $quoteResourceFactory,
        Config $config,
        $connectionName = null
    ) {
        $this->config                   = $config;
        $this->expressionFactory        = $expressionFactory;
        $this->scheduleFactory         = $schedule;
        $this->customerCollection       = $customerCollectionFactory;
        $this->subscribersCollection    = $subscriberCollection;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->orderCollectionFactory   = $orderCollectionFactory;
        $this->quoteResourceFactory = $quoteResourceFactory;
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
            $this->getTable(Schema::EMAIL_CONTACT_TABLE),
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
        $where = ['email_imported = ?' => 1];
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_CONTACT_TABLE),
            ['email_imported' => 0],
            $where
        );

        return $num;
    }

    /**
     * Flag individual contacts for reimport
     *
     * @param array $customerIds
     * @return int
     */
    public function resetContacts(array $customerIds)
    {
        return $this->getConnection()
            ->update(
                $this->getTable(Schema::EMAIL_CONTACT_TABLE),
                ['email_imported' => 0],
                ['customer_id IN (?)' => $customerIds]
            );
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
            $this->getTable(Schema::EMAIL_CONTACT_TABLE),
            ['subscriber_imported' => 0],
            ['subscriber_imported = ?' => 1]
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
                    'last_subscribed_at' => $this->expressionFactory->create(['expression' => 'null']),
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
     * Process unsubscribes from EC, checking whether the user has resubscribed more recently in Magento
     *
     * @param array $unsubscribes
     * @return int
     */
    public function unsubscribeWithResubscriptionCheck(array $unsubscribes)
    {
        if (empty($unsubscribes)) {
            return 0;
        }

        // get emails which either have no last_subscribed_at date, or were more recently removed in EC
        $localContacts = $this->getLastSubscribedAtDates(array_column($unsubscribes, 'email'));
        $unsubscribeEmails = $this->filterRecentlyResubscribedEmails($localContacts, $unsubscribes);

        // no emails to unsubscribe?
        if (empty($unsubscribeEmails)) {
            return 0;
        }

        return $this->unsubscribe($unsubscribeEmails);
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
    public function updateContactsAsGuests($guests, $websiteId)
    {
        $write = $this->getConnection();
        if (! empty($guests)) {
            $where = [
                'email IN (?)' => $guests,
                'website_id = ?' => $websiteId,
                'is_guest IS NULL'
            ];
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
     * @param WebsiteInterface $website
     *
     * @return array
     */
    public function getSalesDataForSubscribersWithOrderStatusesAndBrand($emails, $website)
    {
        $orderStatuses = $this->config->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $website->getId()
        );
        $orderStatuses = explode(',', $orderStatuses);

        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToSelect(['customer_email'])
            ->addExpressionFieldToSelect('total_spend', 'SUM({{grand_total}})', 'grand_total')
            ->addExpressionFieldToSelect('number_of_orders', 'COUNT({{*}})', '*')
            ->addExpressionFieldToSelect('average_order_value', 'AVG({{grand_total}})', 'grand_total')
            ->addFieldToFilter('customer_email', ['in' => $emails])
            ->addFieldToFilter('store_id', $website->getStoreIds());

        $columns = $this->buildCollectionColumns(
            implode(',', $website->getStoreIds())
        );
        $orderCollection->getSelect()
            ->columns($columns)
            ->group('customer_email');

        if (! empty($orderStatuses)) {
            $orderCollection->getSelect()->where('status in (?)', $orderStatuses);
        }

        $orderArray = [];
        foreach ($orderCollection as $item) {
            $orderArray[$item->getCustomerEmail()] = $item->toArray(
                [
                    'total_spend',
                    'number_of_orders',
                    'average_order_value',
                    'last_order_date',
                    'first_order_id',
                    'last_order_id',
                    'last_increment_id',
                    'product_id_for_first_brand',
                    'product_id_for_last_brand',
                    'week_day',
                    'month_day',
                    'product_id_for_most_sold_product'
                ]
            );
        }

        return $orderArray;
    }

    public function getLastSubscribedAtDates(array $emails)
    {
        // get current contact records to check when they last subscribed
        return $this->contactCollectionFactory->create()
            ->addFieldToSelect([
                'email',
                'last_subscribed_at',
            ])
            ->addFieldToFilter('email', ['in' => $emails])
            ->getData();
    }

    /**
     * Filter out any unsubscribes from EC which have recently resubscribed in Magento
     *
     * @param array $localContacts
     * @param array $unsubscribes
     * @return array
     */
    public function filterRecentlyResubscribedEmails(array $localContacts, array $unsubscribes)
    {
        // get emails which either have no last_subscribed_at date, or were more recently removed in EC
        return array_filter(array_map(function ($email) use ($localContacts) {
            // get corresponding local contact
            $contactKey = array_search($email['email'], array_column($localContacts, 'email'));

            // if there is no local contact, or last subscribed value, continue with unsubscribe
            if ($contactKey === false || $localContacts[$contactKey]['last_subscribed_at'] === null) {
                return $email['email'];
            }

            // convert both timestamps to DateTime
            $lastSubscribedMagento = new \DateTime(
                $localContacts[$contactKey]['last_subscribed_at'],
                new \DateTimeZone('UTC')
            );
            $removedAtEc = new \DateTime($email['removed_at'], new \DateTimeZone('UTC'));

            // user recently resubscribed in Magento, do not unsubscribe them
            if ($lastSubscribedMagento > $removedAtEc) {
                return null;
            }
            return $email['email'];
        }, $unsubscribes));
    }

    /**
     * @param string $storeIds
     * @return array
     */
    private function buildCollectionColumns($storeIds)
    {
        $salesOrder = $this->getTable('sales_order');
        $salesOrderItem = $this->getTable('sales_order_item');
        $columns = [
            'last_order_date' => $this->createLastOrderDataColumn($salesOrder, $storeIds),
            'first_order_id' => $this->createFirstOrderIdColumn($salesOrder, $storeIds),
            'last_order_id' => $this->createLastOrderIdColumn($salesOrder, $storeIds),
            'last_increment_id' => $this->createLastIncrementIdColumn($salesOrder, $storeIds),
            'product_id_for_first_brand' => $this->createProductIdForFirstBrandColumn(
                $salesOrder,
                $salesOrderItem,
                $storeIds
            ),
            'product_id_for_last_brand' => $this->createProductIdForLastBrandColumn(
                $salesOrder,
                $salesOrderItem,
                $storeIds
            ),
            'week_day' => $this->createWeekDayColumn($salesOrder, $storeIds),
            'month_day' => $this->createMonthDayColumn($salesOrder, $storeIds),
            'product_id_for_most_sold_product' => $this->createProductIdForMostSoldProductColumn(
                $salesOrder,
                $salesOrderItem,
                $storeIds
            )
        ];

        return $columns;
    }

    /**
     * @param string $salesOrder
     * @param string $storeIds
     * @return \Zend_Db_Expr
     */
    private function createLastOrderDataColumn($salesOrder, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT created_at FROM $salesOrder
                WHERE customer_email = main_table.customer_email
                AND store_id IN ($storeIds)
                ORDER BY created_at DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $storeIds
     * @return \Zend_Db_Expr
     */
    private function createLastOrderIdColumn($salesOrder, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT entity_id FROM $salesOrder
                WHERE customer_email = main_table.customer_email
                AND store_id IN ($storeIds)
                ORDER BY created_at DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $storeIds
     * @return \Dotdigitalgroup\Email\Model\Sql\Expression
     */
    private function createFirstOrderIdColumn($salesOrder, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT entity_id FROM $salesOrder
                WHERE customer_email = main_table.customer_email
                AND store_id IN ($storeIds)
                ORDER BY created_at ASC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $storeIds
     * @return \Zend_Db_Expr
     */
    private function createLastIncrementIdColumn($salesOrder, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT increment_id FROM $salesOrder
                WHERE customer_email = main_table.customer_email
                AND store_id IN ($storeIds)
                ORDER BY created_at DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $storeIds
     * @return \Zend_Db_Expr
     */
    private function createProductIdForFirstBrandColumn($salesOrder, $salesOrderItem, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT sfoi.product_id FROM $salesOrder as sfo
                left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                WHERE sfoi.product_type = 'simple'
                AND customer_email = main_table.customer_email
                AND sfo.store_id IN ($storeIds)
                ORDER BY sfo.created_at ASC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $storeIds
     * @return \Zend_Db_Expr
     */
    private function createProductIdForLastBrandColumn($salesOrder, $salesOrderItem, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT sfoi.product_id FROM $salesOrder as sfo
                left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                WHERE sfoi.product_type = 'simple'
                AND customer_email = main_table.customer_email
                AND sfo.store_id IN ($storeIds)
                ORDER BY sfo.created_at DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $storeIds
     * @return \Zend_Db_Expr
     */
    private function createWeekDayColumn($salesOrder, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT dayname(created_at) as week_day
                FROM $salesOrder
                WHERE customer_email = main_table.customer_email
                AND store_id IN ($storeIds)
                GROUP BY week_day
                HAVING COUNT(*) > 0
                ORDER BY (COUNT(*)) DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * @param string $salesOrder
     * @param string $storeIds
     * @return \Zend_Db_Expr
     */
    private function createMonthDayColumn($salesOrder, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT monthname(created_at) as month_day
                FROM $salesOrder
                WHERE customer_email = main_table.customer_email
                AND store_id IN ($storeIds)
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
     * @param string $storeIds
     * @return \Zend_Db_Expr
     */
    private function createProductIdForMostSoldProductColumn($salesOrder, $salesOrderItem, $storeIds)
    {
        return $this->expressionFactory->create(
            ["expression" => "(
                SELECT sfoi.product_id FROM $salesOrder as sfo
                LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                WHERE customer_email = main_table.customer_email
                AND sfo.store_id IN ($storeIds)
                GROUP BY sfoi.product_id
                HAVING COUNT(sfoi.product_id) > 0
                ORDER BY COUNT(sfoi.product_id) DESC
                LIMIT 1
            )"]
        );
    }

    /**
     * Customer collection with all data ready for export.
     *
     * @param array $customerIds
     * @param array $statuses
     * @param array $storeIds
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSalesDataForCustomersWithOrderStatusesAndBrand($customerIds, $statuses, $storeIds)
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $salesOrder = $orderCollection->getTable('sales_order');
        $salesOrderGrid = $orderCollection->getTable('sales_order_grid');
        $salesOrderItem = $orderCollection->getTable('sales_order_item');

        $orderCollection->addFieldToSelect(['customer_id'])
            ->addExpressionFieldToSelect('total_spend', 'SUM({{grand_total}})', 'grand_total')
            ->addExpressionFieldToSelect('number_of_orders', 'COUNT({{*}})', '*')
            ->addExpressionFieldToSelect('average_order_value', 'AVG({{grand_total}})', 'grand_total')
            ->addFieldToFilter('customer_id', ['in' => $customerIds])
            ->addFieldToFilter('store_id', $storeIds);

        $columnData = $this->buildColumnData(
            $salesOrderGrid,
            $salesOrder,
            $salesOrderItem,
            implode(', ', $storeIds)
        );

        $orderCollection->getSelect()
            ->columns($columnData)
            ->group('customer_id');

        if (! empty($statuses)) {
            $orderCollection->getSelect()->where('status in (?)', $statuses);
        }

        $orderArray = [];
        foreach ($orderCollection as $item) {
            $orderArray[$item->getCustomerId()] = $item->toArray(
                [
                    'total_spend',
                    'number_of_orders',
                    'average_order_value',
                    'last_order_date',
                    'first_order_id',
                    'last_order_id',
                    'last_increment_id',
                    'product_id_for_first_brand',
                    'product_id_for_last_brand',
                    'week_day',
                    'month_day',
                    'product_id_for_most_sold_product'
                ]
            );
        }

        return $this->getCollectionWithLastQuoteId($customerIds, $orderArray, $storeIds);
    }

    /**
     * @param array $customerIds
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    public function buildCustomerCollection($customerIds)
    {
        $customerLog = $this->getTable('customer_log');
        $customerCollection = $this->customerCollection->create()
            ->addAttributeToSelect('*')
            ->addNameToSelect();

        $this->addBillingJoinAttributesToCustomerCollection($customerCollection);
        $this->addShippingJoinAttributesToCustomerCollection($customerCollection);

        $customerCollection->addAttributeToFilter('entity_id', ['in' => $customerIds]);

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

        return $customerCollection;
    }

    /**
     * @param string $salesOrderGrid
     * @param string $salesOrder
     * @param string $salesOrderItem
     * @param string $storeIds
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function buildColumnData($salesOrderGrid, $salesOrder, $salesOrderItem, $storeIds)
    {
        $columnData = [
            'last_order_date' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT created_at
                    FROM $salesOrderGrid
                    WHERE customer_id = main_table.customer_id
                    AND store_id IN ($storeIds)
                    ORDER BY created_at DESC
                    LIMIT 1
                )"]
            ),
            'first_order_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT entity_id
                    FROM $salesOrderGrid
                    WHERE customer_id = main_table.customer_id
                    AND store_id IN ($storeIds)
                    ORDER BY created_at ASC
                    LIMIT 1
                )"]
            ),
            'last_order_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT entity_id
                    FROM $salesOrderGrid
                    WHERE customer_id = main_table.customer_id
                    AND store_id IN ($storeIds)
                    ORDER BY created_at DESC
                    LIMIT 1
                )"]
            ),
            'last_increment_id' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT increment_id
                    FROM $salesOrderGrid
                    WHERE customer_id = main_table.customer_id
                    AND store_id IN ($storeIds)
                    ORDER BY created_at DESC
                    LIMIT 1
                )"]
            ),
            'product_id_for_first_brand' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT sfoi.product_id FROM $salesOrder as sfo
                    left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    WHERE sfo.customer_id = main_table.customer_id
                    AND sfoi.product_type = 'simple'
                    AND sfo.store_id IN ($storeIds)
                    ORDER BY sfo.created_at ASC
                    LIMIT 1
                )"]
            ),
            'product_id_for_last_brand' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT sfoi.product_id FROM $salesOrder as sfo
                    left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    WHERE sfo.customer_id = main_table.customer_id
                    AND sfoi.product_type = 'simple'
                    AND sfo.store_id IN ($storeIds)
                    ORDER BY sfo.created_at DESC
                    LIMIT 1
                )"]
            ),
            'week_day' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT dayname(created_at) as week_day
                    FROM $salesOrder
                    WHERE customer_id = main_table.customer_id
                    AND store_id IN ($storeIds)
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
                    WHERE customer_id = main_table.customer_id
                    AND store_id IN ($storeIds)
                    GROUP BY month_day
                    HAVING COUNT(*) > 0
                    ORDER BY (COUNT(*)) DESC
                    LIMIT 1
                )"]
            ),
            'product_id_for_most_sold_product' => $this->expressionFactory->create(
                ["expression" => "(
                    SELECT sfoi.product_id FROM $salesOrder as sfo
                    LEFT JOIN $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                    WHERE sfo.customer_id = main_table.customer_id
                    AND sfo.store_id IN ($storeIds)
                    GROUP BY sfoi.product_id
                    HAVING COUNT(sfoi.product_id) > 0
                    ORDER BY COUNT(sfoi.product_id) DESC
                    LIMIT 1
                )"]
            )
        ];

        return $columnData;
    }

    /**
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     */
    private function addShippingJoinAttributesToCustomerCollection($customerCollection)
    {
        $customerCollection->joinAttribute(
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
    }

    /**
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     */
    private function addBillingJoinAttributesToCustomerCollection($customerCollection)
    {
        $customerCollection->joinAttribute(
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
    }

    /**
     * Set imported by ids.
     *
     * @param array $ids
     * @param string|int $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setImportedByIds($ids, $websiteId = 0)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['email_imported' => 1],
            [
                "customer_id IN (?)" => $ids,
                "website_id = ?" => $websiteId
            ]
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
        $collection = $this->scheduleFactory->create()
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
            ['email_imported' => 0],
            ["customer_id IN (?)" => $customerIds]
        );
    }

    /**
     * @param array $customerIds
     * @param array $orderArray
     * @param array $storeIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCollectionWithLastQuoteId($customerIds, $orderArray, $storeIds)
    {
        $quoteResource = $this->quoteResourceFactory->create();

        $subQuery = new \Zend_Db_Expr(sprintf(
            "(SELECT customer_id, MAX(entity_id) `last_quote_id` FROM %s
	        WHERE (`customer_id` IN(%s))
	        AND (`store_id` IN(%s))
	        GROUP BY customer_id)",
            $quoteResource->getMainTable(),
            implode(',', $customerIds),
            implode(',', $storeIds)
        ));

        $quoteQuery = $quoteResource->getConnection()
            ->select()
            ->from($subQuery, ['customer_id', 'last_quote_id'])
            ->group('customer_id')
            ->assemble();

        foreach ($quoteResource->getConnection()->query($quoteQuery) as $quote) {
            $customerId = $quote['customer_id'];
            if (isset($orderArray[$customerId])) {
                $orderArray[$customerId]['last_quote_id'] = $quote['last_quote_id'];
            }
        }

        return $orderArray;
    }
}
