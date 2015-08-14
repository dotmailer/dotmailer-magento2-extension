<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Tabs_Analysis_Abandoned extends Mage_Core_Model_Abstract
{
    protected $storeIds;

    /**
     * prepare collection and needed columns
     *
     * @return Mage_Sales_Model_Resource_Quote_Collection
     */
    protected function getPreparedCollection()
    {
        $collection = Mage::getResourceModel('sales/quote_collection');
        $collection
            ->addFieldToFilter('items_count', array('neq' => '0'))
            ->addFieldToFilter('main_table.is_active', '1')
            ->setOrder('updated_at');

        if (is_array($this->storeIds) && !empty($this->storeIds)) {
            $collection->addFieldToFilter('store_id', array('in' => $this->storeIds));
        }

        $adapter = $collection->getConnection();
        $averageExpr = $adapter->getCheckSql(
            'COUNT(main_table.entity_id) > 0',
            'SUM(main_table.subtotal)/COUNT(main_table.entity_id)',
            0);

        $collection->getSelect()->columns(array(
            'lifetime' => 'SUM(main_table.subtotal)',
            'average'  => $averageExpr,
            'total_count'  => "COUNT(main_table.entity_id)",
            'day_count'  => "ROUND(COUNT(main_table.entity_id) / DATEDIFF(date(MAX(main_table.updated_at)) , date(MIN(main_table.updated_at))), 2)"
        ));
        return $collection;
    }

    /**
     * @param int $store
     * @param int $website
     * @param int $group
     * @return Varien_Object
     * @throws Mage_Core_Exception
     */
    public function getLifeTimeAbandoned($store = 0, $website = 0, $group =0)
    {
        if ($store) {
            $this->storeIds = array($store => $store);
        } else if ($website){
            $storeIds = Mage::app()->getWebsite($website)->getStoreIds();
            $this->storeIds = $storeIds;
        } else if ($group){
            $storeIds = Mage::app()->getGroup($group)->getStoreIds();
            $this->storeIds = $storeIds;
        }
        return $this->getPreparedCollection()->getFirstItem();
    }
}