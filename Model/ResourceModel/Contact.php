<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    public $contactFactory;
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    public $subscribersCollection;

    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_contact', 'email_contact_id');
    }

    /**
     * Contact constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        $connectionName = null
    ) {
    
        $this->contactFactory = $contactFactory;
        $this->subscribersCollection = $subscriberCollection;
        parent::__construct($context, $connectionName);
    }

    /**
     * Remove all contact_id from the table.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteContactIds()
    {
        $conn = $this->getConnection();
        try {
            $num = $conn->update(
                $this->getTable('email_contact'),
                ['contact_id' => new \Zend_Db_Expr('null')],
                $conn->quoteInto(
                    'contact_id is ?',
                    new \Zend_Db_Expr('not null')
                )
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Reset the imported contacts.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetAllContacts()
    {
        try {
            $conn = $this->getConnection();
            $num = $conn->update(
                $conn->getTableName('email_contact'),
                ['email_imported' => new \Zend_Db_Expr('null')],
                $conn->quoteInto(
                    'email_imported is ?',
                    new \Zend_Db_Expr('not null')
                )
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Set all imported subscribers for reimport.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetSubscribers()
    {
        $conn = $this->getConnection();

        try {
            $num = $conn->update(
                $conn->getTableName('email_contact'),
                ['subscriber_imported' => new \Zend_Db_Expr('null')],
                $conn->quoteInto(
                    'subscriber_imported is ?',
                    new \Zend_Db_Expr('not null')
                )
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }

    /**
     * Unsubscribe a contact from email_contact/newsletter table.
     *
     * @param $data
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function unsubscribe($data)
    {
        if (empty($data)) {
            return 0;
        }
        $write = $this->getConnection();
        $emails = '"' . implode('","', $data) . '"';

        try {
            //un-subscribe from the email contact table.
            $updated = $write->update(
                $this->getMainTable(),
                [
                    'is_subscriber' => new \Zend_Db_Expr('null'),
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
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $updated;
    }

    /**
     * @param $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertGuest($data)
    {
        $contacts = array_keys($data);
        $contactModel = $this->contactFactory->create();
        $emailsExistInTable = $contactModel->getCollection()
            ->addFieldToFilter('email', ['in' => $contacts])
            ->getColumnValues('email');

        $guests = array_diff_key($data, array_flip($emailsExistInTable));

        if (! empty($guests)) {
            try {
                $write = $this->getConnection();
                $write->insertMultiple($this->getMainTable(), $guests);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
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
     * @param $ids array
     * @return int
     */
    public function updateSubscribers($ids)
    {
        if (empty($ids)) {
            return 0;
        }
        $write = $this->getConnection();
        $ids = implode(', ', $ids);
        //update subscribers imported
        $updated = $write->update(
            $this->getMainTable(),
            ['subscriber_imported' => 1],
            ["email_contact_id IN (?)" => $ids]
        );

        return $updated;
    }

    /**
     * Get collection for subscribers by emails
     *
     * @param $emails
     * @param int $websiteId
     * @param $statuses
     * @return \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    public function getCollectionForSubscribersByEmails($emails, $websiteId = 0, $statuses)
    {
        $connection = $this->getConnection();

        $salesOrder = $connection->getTableName('sales_order');
        $salesOrderItem = $connection->getTableName('sales_order_item');
        $catalogProductEntityInt = $connection->getTableName('catalog_product_entity_int');
        $eavAttribute = $connection->getTableName('eav_attribute');
        $eavAttributeOptionValue = $connection->getTableName('eav_attribute_option_value');
        $catalogCategoryProductIndex = $connection->getTableName('catalog_category_product');

        $collection = $this->subscribersCollection->create()
            ->addFieldToSelect([
                'subscriber_email',
                'store_id',
                'subscriber_status'
            ]);

        //only when subscriber emails are set
        if (! empty($emails)) {
            $collection->addFieldToFilter('subscriber_email', $emails);
        }

        $alias = 'subselect';

        $subselect = $connection->select()
            ->from(
                $salesOrder, [
                    'customer_email as s_customer_email',
                    'sum(grand_total) as total_spend',
                    'count(*) as number_of_orders',
                    'avg(grand_total) as average_order_value',
                ]
            )
            ->group('customer_email');

        //any order statuses selected
        if (! empty($statuses)) {
            $subselect->where('status in (?)', $statuses);
        }

        $columns = [
            'last_order_date' => new \Zend_Db_Expr(
                "(
                        SELECT created_at FROM $salesOrder 
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
            ),
            'last_order_id' => new \Zend_Db_Expr(
                "(
                        SELECT entity_id FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
            ),
            'last_increment_id' => new \Zend_Db_Expr(
                "(
                        SELECT increment_id FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
            ),
            'first_category_id' => new \Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'last_category_id' => new \Zend_Db_Expr(
                "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'product_id_for_first_brand' => new \Zend_Db_Expr(
                "(
                        SELECT sfoi.product_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_email = main_table.subscriber_email and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'product_id_for_last_brand' => new \Zend_Db_Expr(
                "(
                        SELECT sfoi.product_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_email = main_table.subscriber_email and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
            ),
            'week_day' => new \Zend_Db_Expr(
                "(
                        SELECT dayname(created_at) as week_day
                        FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email
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
                        WHERE customer_email = main_table.subscriber_email
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
                        WHERE sfo.customer_email = main_table.subscriber_email AND ccpi.category_id is not null
                        GROUP BY category_id
                        HAVING COUNT(sfoi.product_id) > 0
                        ORDER BY COUNT(sfoi.product_id) DESC
                        LIMIT 1
                    )"
            )
        ];

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
                    WHERE sfo.customer_email = main_table.subscriber_email AND ea.attribute_code = 'manufacturer' AND eaov.value is not null
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
                    WHERE sfo.customer_email = main_table.subscriber_email AND ea.attribute_code = 'manufacturer' AND eaov.value is not null
                    GROUP BY eaov.value
                    HAVING count(*) > 0
                    ORDER BY count(*) DESC
                    LIMIT 1
                )"
            );

        }

        $columns['most_brand'] = $mostData;
        $collection->getSelect()->columns(
            $columns
        );

        $collection->getSelect()
            ->joinLeft(
                [$alias => $subselect],
                "{$alias}.s_customer_email = main_table.subscriber_email"
            );

        return $collection;
    }

    /**
     * @return bool
     */
    private function isRowIdExistsInCatalogProductEntityId()
    {
        $connection = $this->getConnection();

        return $connection->tableColumnExists(
            $this->connectionName->getTableName('catalog_product_entity_int'),
            'row_id'
        );
    }
}
