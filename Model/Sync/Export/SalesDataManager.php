<?php

namespace Dotdigitalgroup\Email\Model\Sync\Export;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Website;

class SalesDataManager
{
    /**
     * @var Datafield
     */
    private $datafield;

    /**
     * @var SalesOrderCollectionFactory
     */
    private $salesOrderCollectionFactory;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $salesDataArray = [];

    /**
     * @var array
     */
    private $columns;

    /**
     * SalesDataManager constructor.
     *
     * @param Datafield $datafield
     * @param SalesOrderCollectionFactory $salesOrderCollectionFactory
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Datafield $datafield,
        SalesOrderCollectionFactory $salesOrderCollectionFactory,
        QuoteCollectionFactory $quoteCollectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->datafield = $datafield;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Collects an array of data for any mapped 'sales' data fields.
     *
     * We deal with email addresses in this class (i.e. we pass in $emails
     * and key the salesDataArray on email) because we need to fetch sales data
     * for both customer contacts and subscriber contacts.
     *
     * @param array $emails
     * @param WebsiteInterface $website
     * @param array $columns
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setContactSalesData(array $emails, WebsiteInterface $website, $columns)
    {
        $this->salesDataArray = [];
        $this->setColumns($columns);
        if (!$this->salesDataFieldsAreMapped()) {
            return [];
        }

        /** @var Website $website */
        $statuses = $this->getOrderStatuses($website->getId());
        $storeIds = $website->getStoreIds();

        if ($this->salesDataFieldsAreMapped([
            'total_spend',
            'total_refund',
            'number_of_orders',
            'average_order_value',
            'last_order_date',
            'last_order_id',
            'first_brand_pur',
            'last_brand_pur'
        ])) {
            foreach ($this->fetchBaseOrderCollection($emails, $storeIds, $statuses) as $item) {
                $this->updateSalesDataArrayFromCollectionItem($item, [
                    'total_spend',
                    'total_refund',
                    'number_of_orders',
                    'average_order_value',
                    'last_order_date',
                    'first_order_id',
                    'last_order_id'
                ]);
            }
        }

        if ($this->salesDataFieldsAreMapped(['last_increment_id'])) {
            $lastOrderIds = array_column($this->salesDataArray, 'last_order_id');
            foreach ($this->fetchLastIncrementIds($lastOrderIds) as $row) {
                $customerEmail = $row['customer_email'];
                $this->salesDataArray[$customerEmail]['last_increment_id'] = $row['increment_id'];
            }
        }

        if ($this->salesDataFieldsAreMapped(['first_brand_pur', 'first_category_pur'])) {
            $firstOrderIds = array_column($this->salesDataArray, 'first_order_id');
            foreach ($this->fetchOrderedProductIds($firstOrderIds) as $row) {
                $customerEmail = $row['customer_email'];
                $this->salesDataArray[$customerEmail]['product_ids_for_first_order'][] = $row['product_id'];
            }
        }

        if ($this->salesDataFieldsAreMapped(['last_brand_pur', 'last_category_pur'])) {
            $lastOrderIds = array_column($this->salesDataArray, 'last_order_id');
            foreach ($this->fetchOrderedProductIds($lastOrderIds) as $row) {
                $customerEmail = $row['customer_email'];
                $this->salesDataArray[$customerEmail]['product_ids_for_last_order'][] = $row['product_id'];
            }
        }

        if ($this->salesDataFieldsAreMapped(['most_freq_pur_day'])) {
            foreach ($this->fetchWeekDays($emails, $storeIds, $statuses) as $row) {
                $this->updateSalesDataArrayFromResultRow($row, 'week_day', 'week_day');
            }
        }

        if ($this->salesDataFieldsAreMapped(['most_freq_pur_mon'])) {
            foreach ($this->fetchMonths($emails, $storeIds, $statuses) as $row) {
                $this->updateSalesDataArrayFromResultRow($row, 'month', 'month');
            }
        }

        if ($this->salesDataFieldsAreMapped(['most_pur_brand', 'most_pur_category'])) {
            foreach ($this->fetchMostSoldProductIds($emails, $storeIds, $statuses) as $row) {
                $this->updateSalesDataArrayFromResultRow($row, 'product_id', 'product_id_for_most_sold_product');
            }
        }

        if ($this->salesDataFieldsAreMapped(['last_quote_id'])) {
            foreach ($this->fetchLastQuoteIds($emails, $storeIds) as $item) {
                $this->updateSalesDataArrayFromCollectionItem($item, ['last_quote_id']);
            }
        }

        return $this->salesDataArray;
    }

    /**
     * Fetches an order collection with some additional sum/count/avg columns.
     *
     * @param array $emails
     * @param array $storeIds
     * @param array $statuses
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection|array
     */
    private function fetchBaseOrderCollection($emails, $storeIds, $statuses)
    {
        $baseOrderCollection = $this->salesOrderCollectionFactory->create()
            ->addFieldToSelect(['customer_email'])
            ->addExpressionFieldToSelect('total_spend', 'SUM(grand_total)', 'grand_total')
            ->addExpressionFieldToSelect('total_refund', 'SUM(total_refunded)', 'total_refunded')
            ->addExpressionFieldToSelect('number_of_orders', 'COUNT(*)', '*')
            ->addExpressionFieldToSelect('average_order_value', 'AVG(grand_total)', 'grand_total')
            ->addExpressionFieldToSelect('last_order_date', 'MAX(created_at)', 'created_at')
            ->addExpressionFieldToSelect('first_order_id', 'MIN(entity_id)', 'entity_id')
            ->addExpressionFieldToSelect('last_order_id', 'MAX(entity_id)', 'entity_id')
            ->addFieldToFilter('customer_email', ['in' => $emails])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        $baseOrderCollection->getSelect()->group('customer_email');

        if (!empty($statuses)) {
            $baseOrderCollection->getSelect()->where('status in (?)', $statuses);
        }

        return $baseOrderCollection;
    }

    /**
     * Fetch last increment ids.
     *
     * @param array $orderIds
     *
     * @return array
     */
    private function fetchLastIncrementIds(array $orderIds)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        $connection = $collection->getResource()->getConnection();

        $select = $connection->select()
            ->from([
                'sales_order' => $collection->getMainTable()
            ], [
                'customer_email',
                'increment_id'
            ])
            ->where(
                'entity_id IN (?)',
                $orderIds
            );

        return $connection->fetchAll($select);
    }

    /**
     * Fetches an array of product ids from an array of order IDs.
     *
     * @param array $orderIds
     *
     * @return array
     */
    private function fetchOrderedProductIds(array $orderIds)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        $connection = $collection->getResource()->getConnection();

        $select = $connection->select()
            ->from([
                'sales_order' => $collection->getMainTable()
            ], [
                'customer_email',
                'sales_order_item.product_id'
            ])
            ->joinLeft(
                ['sales_order_item' => $collection->getTable('sales_order_item')],
                'sales_order_item.order_id = sales_order.entity_id',
                []
            )
            ->where(
                'entity_id IN (?)',
                $orderIds
            );

        return $connection->fetchAll($select);
    }

    /**
     * Fetches the week days, by customer, on which most orders were placed.
     *
     * @param array $emails
     * @param array $storeIds
     * @param array $statuses
     *
     * @return array
     */
    private function fetchWeekDays($emails, $storeIds, $statuses)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        $connection = $collection->getResource()->getConnection();
        $select = $connection->select()
            ->from([
                'sales_order' => $collection->getMainTable(),
            ], [
                'customer_email',
                'week_day' => 'dayname(created_at)'
            ])
            ->where('customer_email IN (?)', $emails)
            ->where('sales_order.store_id IN (?)', $storeIds)
            ->order(new \Zend_Db_Expr('(COUNT(*)) DESC'));

        if (!empty($statuses)) {
            $select->where('sales_order.status in (?)', $statuses);
        }

        $select->group('customer_email');

        return $connection->fetchAll($select);
    }

    /**
     * Fetches the months, by customer, in which most orders were placed.
     *
     * @param array $emails
     * @param array $storeIds
     * @param array $statuses
     *
     * @return array
     */
    private function fetchMonths($emails, $storeIds, $statuses)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        $connection = $collection->getResource()->getConnection();
        $select = $connection->select()
            ->from([
                'sales_order' => $collection->getMainTable(),
            ], [
                'customer_email',
                'month' => 'monthname(created_at)'
            ])
            ->where('customer_email IN (?)', $emails)
            ->where('sales_order.store_id IN (?)', $storeIds)
            ->order(new \Zend_Db_Expr('(COUNT(*)) DESC'));

        if (!empty($statuses)) {
            $select->where('sales_order.status in (?)', $statuses);
        }

        $select->group('customer_email');

        return $connection->fetchAll($select);
    }

    /**
     * Fetches the one product id, by customer, that has been ordered most.
     *
     * @param array $emails
     * @param array $storeIds
     * @param array $statuses
     *
     * @return array
     */
    private function fetchMostSoldProductIds($emails, $storeIds, $statuses)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        $connection = $collection->getResource()->getConnection();
        $subSelect = $connection->select()
            ->from([
                'sales_order' => $collection->getMainTable(),
            ], [
                'customer_email',
                'sales_order_item.product_id'
            ])
            ->joinLeft(
                ['sales_order_item' => $collection->getTable('sales_order_item')],
                'sales_order_item.order_id = sales_order.entity_id',
                []
            )
            ->where('customer_email IN (?)', $emails)
            ->where('sales_order.store_id IN (?)', $storeIds)
            ->order(new \Zend_Db_Expr('COUNT(*) DESC'));

        if (!empty($statuses)) {
            $subSelect->where('sales_order.status in (?)', $statuses);
        }

        $subSelect->group(['sales_order_item.product_id', 'customer_email']);

        $select = $connection->select()
            ->from([
                'sub' => $subSelect
            ])
            ->group('customer_email');

        return $connection->fetchAll($select);
    }

    /**
     * Find last quote ids for supplied customers.
     *
     * @param array $emails
     * @param array $storeIds
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    private function fetchLastQuoteIds($emails, $storeIds)
    {
        $quoteCollection = $this->quoteCollectionFactory->create()
            ->addExpressionFieldToSelect('last_quote_id', 'MAX(entity_id)', 'entity_id')
            ->addFieldToFilter('customer_email', ['in' => $emails])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        $quoteCollection->getSelect()->group('customer_email');

        return $quoteCollection;
    }

    /**
     * Check if sales data fields are mapped.
     *
     * Determine if specific sales data fields are included in the columns for the current website.
     * If no fields are supplied, we check if _any_ sales data columns are included.
     *
     * @param array $fields
     * @return bool
     */
    private function salesDataFieldsAreMapped($fields = [])
    {
        $salesDataFields = empty($fields) ?
            array_keys($this->datafield->getSalesDatafields()):
            $fields;
        $mapped = array_intersect($salesDataFields, array_keys($this->columns));
        return count($mapped) > 0;
    }

    /**
     * Update sales data array.
     *
     * @param \Magento\Sales\Model\Order $item
     * @param array $keys
     *
     * @return void
     */
    private function updateSalesDataArrayFromCollectionItem($item, $keys)
    {
        foreach ($item->toArray($keys) as $key => $value) {
            $this->salesDataArray[$item->getCustomerEmail()][$key] = $value;
        }
    }

    /**
     * Update sales data array.
     *
     * @param array $row
     * @param string $rowKey
     * @param string $dataKey
     *
     * @return void
     */
    private function updateSalesDataArrayFromResultRow($row, $rowKey, $dataKey)
    {
        $customerEmail = $row['customer_email'];
        $this->salesDataArray[$customerEmail][$dataKey] = $row[$rowKey];
    }

    /**
     * Store columns.
     *
     * @param array $columns
     *
     * @return void
     */
    private function setColumns($columns)
    {
        $this->columns = $columns;
    }

    /**
     * Get allowed order statuses for sync.
     *
     * @param string|int $websiteId
     *
     * @return array
     */
    private function getOrderStatuses($websiteId)
    {
        $statuses = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        return explode(',', $statuses ?: '');
    }
}
