<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Tabs_Analysis_Orders extends Mage_Core_Model_Abstract
{
    /**
     * calculate sales and prepare columns
     *
     * @param int $isFilter
     * @return Mage_Reports_Model_Resource_Order_Collection
     */
    protected function calculateSales($isFilter = 0)
    {
        $collection = Mage::getResourceModel('reports/order_collection');

        $statuses = Mage::getSingleton('sales/config')
            ->getOrderStatusesForState(Mage_Sales_Model_Order::STATE_CANCELED);

        if (empty($statuses)) {
            $statuses = array(0);
        }
        $adapter = $collection->getConnection();

        if (Mage::getStoreConfig('sales/dashboard/use_aggregated_data')) {
            $collection->setMainTable('sales/order_aggregated_created');
            $collection->removeAllFieldsFromSelect();
            $averageExpr = $adapter->getCheckSql(
                'SUM(main_table.orders_count) > 0',
                'SUM(main_table.total_revenue_amount)/SUM(main_table.orders_count)',
                0);
            $collection->getSelect()->columns(array(
                'lifetime' => 'SUM(main_table.total_revenue_amount)',
                'average'  => $averageExpr,
                'total_count'  => "SUM(main_table.orders_count)",
                'day_count'  => "ROUND(SUM(main_table.orders_count) / DATEDIFF(date(MAX(period)) , date(MIN(period))), 2)"
            ));

            if (!$isFilter) {
                $collection->addFieldToFilter('store_id',
                    array('eq' => Mage::app()->getStore(Mage_Core_Model_Store::ADMIN_CODE)->getId())
                );
            }
            $collection->getSelect()->where('main_table.order_status NOT IN(?)', $statuses);
        } else {
            $collection->setMainTable('sales/order');
            $collection->removeAllFieldsFromSelect();

            $expr = $this->_getSalesAmountExpression($collection);

            if ($isFilter == 0) {
                $expr = '(' . $expr . ') * main_table.base_to_global_rate';
            }

            $collection->getSelect()
                ->columns(array(
                    'lifetime' => "SUM({$expr})",
                    'average'  => "AVG({$expr})",
                    'total_count'  => "COUNT({$expr})",
                    'day_count'  => "ROUND(COUNT({$expr}) / DATEDIFF(date(MAX(created_at)) , date(MIN(created_at))), 2)"
                ))
                ->where('main_table.status NOT IN(?)', $statuses)
                ->where('main_table.state NOT IN(?)', array(
                        Mage_Sales_Model_Order::STATE_NEW,
                        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
                );
        }
        return $collection;
    }

    /**
     * get sales amount expression
     *
     * @param $collection
     * @return string
     */
    protected function _getSalesAmountExpression($collection)
    {
        $adapter = $collection->getConnection();
        $expressionTransferObject = new Varien_Object(array(
            'expression' => '%s - %s - %s - (%s - %s - %s)',
            'arguments' => array(
                $adapter->getIfNullSql('main_table.base_total_invoiced', 0),
                $adapter->getIfNullSql('main_table.base_tax_invoiced', 0),
                $adapter->getIfNullSql('main_table.base_shipping_invoiced', 0),
                $adapter->getIfNullSql('main_table.base_total_refunded', 0),
                $adapter->getIfNullSql('main_table.base_tax_refunded', 0),
                $adapter->getIfNullSql('main_table.base_shipping_refunded', 0),
            )
        ));

        return vsprintf(
                $expressionTransferObject->getExpression(),
                $expressionTransferObject->getArguments()
            );

    }

    /**
     * @param int $store
     * @param int $website
     * @param int $group
     * @return Varien_Object
     * @throws Mage_Core_Exception
     */
    public function getLifetimeSales($store = 0, $website = 0, $group =0)
    {
        $isFilter = $store || $website || $group;
        $collection = $this->calculateSales($isFilter);

        if ($store) {
            $collection->addFieldToFilter('store_id', $store);
        } else if ($website){
            $storeIds = Mage::app()->getWebsite($website)->getStoreIds();
            $collection->addFieldToFilter('store_id', array('in' => $storeIds));
        } else if ($group){
            $storeIds = Mage::app()->getGroup($group)->getStoreIds();
            $collection->addFieldToFilter('store_id', array('in' => $storeIds));
        }

        $collection->load();
        return $collection->getFirstItem();
    }
}