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
            'first_order_id',
            'last_order_id',
            'last_increment_id'
        ])) {
            foreach ($this->fetchBaseOrderCollection($emails, $storeIds, $statuses) as $item) {
                $this->updateSalesDataArrayFromCollectionItem($item, [
                    'total_spend',
                    'total_refund',
                    'number_of_orders',
                    'average_order_value',
                    'last_order_date',
                    'first_order_id',
                    'last_order_id',
                    'last_increment_id'
                ]);
            }
        }

        if ($this->salesDataFieldsAreMapped(['first_brand_pur'])) {
            $firstOrderIds = $this->getOrderIds($emails, $storeIds, $statuses, 'first');
            foreach ($this->fetchOrderedProductIds($firstOrderIds) as $row) {
                $this->updateSalesDataArrayFromResultRow($row, 'product_id', 'product_id_for_first_brand');
            }
        }

        if ($this->salesDataFieldsAreMapped(['last_brand_pur'])) {
            $lastOrderIds = $this->getOrderIds($emails, $storeIds, $statuses, 'last');
            foreach ($this->fetchOrderedProductIds($lastOrderIds) as $row) {
                $this->updateSalesDataArrayFromResultRow($row, 'product_id', 'product_id_for_last_brand');
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
            ->addExpressionFieldToSelect('last_increment_id', 'MAX(increment_id)', 'increment_id')
            ->addFieldToFilter('customer_email', ['in' => $emails])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        $baseOrderCollection->getSelect()->group('customer_email');

        if (!empty($statuses)) {
            $baseOrderCollection->getSelect()->where('status in (?)', $statuses);
        }

        return $baseOrderCollection;
    }

    /**
     * Fetches the first product id from an array of order IDs.
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

        $select->group('customer_email');

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
            $select->where('status in (?)', $statuses);
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
            $select->where('status in (?)', $statuses);
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
            $subSelect->where('status in (?)', $statuses);
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
     * Fetches the first or last order IDs for a batch of emails.
     *
     * @param array $emails
     * @param array $storeIds
     * @param array $statuses
     * @param string $firstOrLast
     *
     * @return array
     */
    private function getOrderIds(array $emails, array $storeIds, array $statuses, string $firstOrLast)
    {
        $collection = $this->salesOrderCollectionFactory->create();
        $connection = $collection->getResource()->getConnection();

        $select = $connection->select()
            ->from(
                [
                    'sales_order' => $collection->getMainTable()
                ],
                $this->getOrderIdKeyPair($firstOrLast)
            )
            ->joinLeft(
                ['sales_order_item' => $collection->getTable('sales_order_item')],
                'sales_order_item.order_id = sales_order.entity_id',
                []
            )
            ->where('customer_email IN (?)', $emails)
            ->where('sales_order.store_id IN (?)', $storeIds)
            ->where('sales_order_item.product_type = ?', 'simple');

        if (!empty($statuses)) {
            $select->where('status in (?)', $statuses);
        }
        $select->group('customer_email');

        return $connection->fetchAll($select);
    }

    /**
     * Get order id filter key pair.
     *
     * @param string $firstOrLast
     *
     * @return string[]
     */
    private function getOrderIdKeyPair($firstOrLast)
    {
        return $firstOrLast === 'first' ?
            ['first_order_id' => 'MIN(entity_id)'] :
            ['last_order_id' => 'MAX(entity_id)'];
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
