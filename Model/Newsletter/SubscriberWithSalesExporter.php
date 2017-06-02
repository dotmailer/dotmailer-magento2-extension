<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;


class SubscriberWithSalesExporter
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $file;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    public $subscribersCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\SubscriberFactory
     */
    public $emailSubscriber;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    public $emailContactResource;

    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Dotdigitalgroup\Email\Model\Apiconnector\SubscriberFactory $emailSubscriber,
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactResource){
        $this->importerFactory   = $importerFactory;
        $this->file              = $file;
        $this->helper            = $helper;
        $this->resource          = $resource;
        $this->subscribersCollection = $subscriberCollection;
        $this->emailSubscriber = $emailSubscriber;
        $this->emailContactResource = $contactResource;
    }

    /**
     * @param $website
     * @param $subscribers
     * @return int
     */
    public function exportSubscribersWithSales($website, $subscribers)
    {
        $updated = 0;
        $subscriberIds = $headers = $emailContactIdEmail = [];

        foreach ($subscribers as $emailContact) {
            $emailContactIdEmail[$emailContact->getId()] = $emailContact->getEmail();
        }
        $subscribersFile = strtolower($website->getCode() . '_subscribers_with_sales_' . date('d_m_Y_Hi') . '.csv');
        $this->helper->log('Subscriber file with sales : ' . $subscribersFile);
        //get subscriber emails
        $emails = $subscribers->getColumnValues('email');

        //subscriber collection
        $collection = $this->getCollection($emails, $website->getId());
        //no subscribers found
        if ($collection->getSize() == 0) {
            return 0;
        }
        $mappedHash = $this->helper->getWebsiteSalesDataFields($website);
        $headers = $mappedHash;
        $headers[] = 'Email';
        $headers[] = 'EmailType';
        $this->file->outputCSV($this->file->getFilePath($subscribersFile), $headers);
        //subscriber data
        foreach ($collection as $subscriber) {
            $connectorSubscriber = $this->emailSubscriber->create();
            $connectorSubscriber->setMappingHash($mappedHash);
            $connectorSubscriber->setSubscriberData($subscriber);
            //count number of customers
            $index = array_search($subscriber->getSubscriberEmail(), $emailContactIdEmail);
            if ($index) {
                $subscriberIds[] = $index;
            }
            //contact email and email type
            $connectorSubscriber->setData($subscriber->getSubscriberEmail());
            $connectorSubscriber->setData('Html');
            // save csv file data
            $this->file->outputCSV($this->file->getFilePath($subscribersFile), $connectorSubscriber->toCSVArray());
            //clear collection and free memory
            $subscriber->clearInstance();
            $updated++;
        }

        $subscriberNum = count($subscriberIds);
        //@codingStandardsIgnoreStart
        if (is_file($this->file->getFilePath($subscribersFile))) {
            //@codingStandardsIgnoreEnd
            if ($subscriberNum > 0) {
                //register in queue with importer
                $check = $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_SUBSCRIBERS,
                        '',
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId(),
                        $subscribersFile
                    );
                //set imported
                if ($check) {
                    $this->emailContactResource->create()
                        ->updateSubscribers($subscriberIds);
                }
            }
        }

        return $updated;
    }

    /**
     * @param $emails
     * @param int $websiteId
     * @return mixed
     */
    public function getCollection($emails, $websiteId = 0)
    {
        $salesOrder = $this->resource->getTableName('sales_order');
        $salesOrderItem = $this->resource->getTableName('sales_order_item');
        $catalogProductEntityInt = $this->resource->getTableName('catalog_product_entity_int');
        $eavAttribute = $this->resource->getTableName('eav_attribute');
        $eavAttributeOptionValue = $this->resource->getTableName('eav_attribute_option_value');
        $catalogCategoryProductIndex = $this->resource->getTableName('catalog_category_product');

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
        $statuses = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            $websiteId
        );
        $statuses = explode(',', $statuses);

        $connection = $this->resource->getConnection();
        //@codingStandardsIgnoreStart
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

        $columns = $this->buildCollectionColumns($salesOrder, $salesOrderItem, $catalogCategoryProductIndex);

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
        //@codingStandardsIgnoreEnd
        return $collection;
    }

    /**
     * @return bool
     */
    private function isRowIdExistsInCatalogProductEntityId()
    {
        $connection = $this->resource->getConnection();

        return $connection->tableColumnExists(
            $this->resource->getTableName('catalog_product_entity_int'),
            'row_id'
        );
    }

    /**
     * @param $salesOrder
     * @param $salesOrderItem
     * @param $catalogCategoryProductIndex
     * @return array
     */
    private function buildCollectionColumns($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        $columns = [
            'last_order_date' => $this->createLastOrderDataColumn($salesOrder),
            'last_order_id' => $this->createLastOrderIdColumn($salesOrder),
            'last_increment_id' => $this->createLastIncrementIdColumn($salesOrder),
            'first_category_id' => $this->createFirstCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex),
            'last_category_id' => $this->createLastCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex),
            'product_id_for_first_brand' => $this->createProductIdForFirstBrandColumn($salesOrder, $salesOrderItem),
            'product_id_for_last_brand' => $this->createProductIdForLastBrandColumn($salesOrder, $salesOrderItem),
            'week_day' => $this->createWeekDayColumn($salesOrder),
            'month_day' => $this->createMonthDayColumn($salesOrder),
            'most_category_id' => $this->createMostCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
        ];
        return $columns;
    }

    /**
     * @param $salesOrder
     * @return \Zend_Db_Expr
     */
    private function createLastOrderDataColumn($salesOrder)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT created_at FROM $salesOrder 
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
        );
    }

    /**
     * @param $salesOrder
     * @return \Zend_Db_Expr
     */
    private function createLastOrderIdColumn($salesOrder)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT entity_id FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
        );
    }

    /**
     * @param $salesOrder
     * @return \Zend_Db_Expr
     */
    private function createLastIncrementIdColumn($salesOrder)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT increment_id FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email 
                        ORDER BY created_at DESC 
                        LIMIT 1
                )"
        );
    }

    /**
     * @param $salesOrder
     * @param $salesOrderItem
     * @param $catalogCategoryProductIndex
     * @return \Zend_Db_Expr
     */
    private function createFirstCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
        );
    }

    /**
     * @param $salesOrder
     * @param $salesOrderItem
     * @param $catalogCategoryProductIndex
     * @return \Zend_Db_Expr
     */
    private function createLastCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT ccpi.category_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        left join $catalogCategoryProductIndex as ccpi on ccpi.product_id = sfoi.product_id
                        WHERE sfo.customer_email = main_table.subscriber_email
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
        );
    }

    /**
     * @param $salesOrder
     * @param $salesOrderItem
     * @return \Zend_Db_Expr
     */
    private function createProductIdForFirstBrandColumn($salesOrder, $salesOrderItem)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT sfoi.product_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_email = main_table.subscriber_email and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at ASC, sfoi.price DESC
                        LIMIT 1
                    )"
        );
    }

    /**
     * @param $salesOrder
     * @param $salesOrderItem
     * @return \Zend_Db_Expr
     */
    private function createProductIdForLastBrandColumn($salesOrder, $salesOrderItem)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT sfoi.product_id FROM $salesOrder as sfo
                        left join $salesOrderItem as sfoi on sfoi.order_id = sfo.entity_id
                        WHERE sfo.customer_email = main_table.subscriber_email and sfoi.product_type = 'simple'
                        ORDER BY sfo.created_at DESC, sfoi.price DESC
                        LIMIT 1
                    )"
        );
    }

    /**
     * @param $salesOrder
     * @return \Zend_Db_Expr
     */
    private function createWeekDayColumn($salesOrder)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT dayname(created_at) as week_day
                        FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email
                        GROUP BY week_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
        );
    }

    /**
     * @param $salesOrder
     * @return \Zend_Db_Expr
     */
    private function createMonthDayColumn($salesOrder)
    {
        return new \Zend_Db_Expr(
            "(
                        SELECT monthname(created_at) as month_day
                        FROM $salesOrder
                        WHERE customer_email = main_table.subscriber_email
                        GROUP BY month_day
                        HAVING COUNT(*) > 0
                        ORDER BY (COUNT(*)) DESC
                        LIMIT 1
                    )"
        );
    }

    /**
     * @param $salesOrder
     * @param $salesOrderItem
     * @param $catalogCategoryProductIndex
     * @return \Zend_Db_Expr
     */
    private function createMostCategoryIdColumn($salesOrder, $salesOrderItem, $catalogCategoryProductIndex)
    {
        return new \Zend_Db_Expr(
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
        );
    }
}