<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Tabs_Analysis_Customer extends Mage_Core_Model_Abstract
{
    protected $storeIds;

    /**
     * prepare collection and needed columns
     *
     * @return Mage_Customer_Model_Resource_Customer_Collection
     */
    protected function getPreparedCollection()
    {
        $collection = Mage::getResourceModel('customer/customer_collection');

        if (is_array($this->storeIds) && !empty($this->storeIds)) {
            $collection->addAttributeToFilter('store_id', array('in' => $this->storeIds));
        }

        $collection->getSelect()->columns(array(
            'total_count'  => "COUNT(*)",
            'day_count'  => "ROUND(COUNT(*) / DATEDIFF(date(MAX(created_at)) , date(MIN(created_at))), 2)"
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
    public function getLifeTimeTimeCustomer($store = 0, $website = 0, $group =0)
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