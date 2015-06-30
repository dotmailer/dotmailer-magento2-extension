<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Tabs_Analysis_Rfm extends Mage_Core_Model_Abstract
{
	protected $rfm = array();
	protected $_store = 0;
	protected $_group = 0;
	protected $_website = 0;

    protected $_resultCount;

	const RECENCY       =   'Recency';
	const FREQUENCY     =   'Frequency';
	const MONETARY      =   'Monetary';

	/**
	 * prepare collection and needed columns
	 *
	 * @return Mage_Sales_Model_Resource_Order_Collection
	 * @throws Mage_Core_Exception
	 */
	protected function getPreparedCollection()
	{
		$statuses = Mage::getSingleton('sales/config')
		                ->getOrderStatusesForState(Mage_Sales_Model_Order::STATE_CANCELED);
		if (empty($statuses)) {
			$statuses = array(0);
		}

		$collection = Mage::getResourceModel('sales/order_collection');
		$collection
			->addFieldToFilter('status', array('nin' => $statuses))
			->addFieldToFilter('state',
				array('nin' => array(
					Mage_Sales_Model_Order::STATE_NEW,
					Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
				)
			)
			->addFieldToFilter('customer_id', array('neq' => 'null'))
			->addOrder('created_at');

		if ($this->_store) {
			$collection->addFieldToFilter('store_id', $this->_store);
		} else if ($this->_website){
			$storeIds = Mage::app()->getWebsite($this->_website)->getStoreIds();
			$collection->addFieldToFilter('store_id', array('in' => $storeIds));
		} else if ($this->_group){
			$storeIds = Mage::app()->getGroup($this->_group)->getStoreIds();
			$collection->addFieldToFilter('store_id', array('in' => $storeIds));
		}

		$expr = $this->_getSalesAmountExpression($collection);
		$isFilter = $this->_store || $this->_website || $this->_group;
		if ($isFilter == 0) {
			$expr = '(' . $expr . ') * main_table.base_to_global_rate';
		}

		$collection->getSelect()
		           ->reset(Zend_Db_Select::COLUMNS)
		           ->columns(array(
			           'customer_total_orders' => "count(*)",
			           'customer_average_order_value' => "SUM({$expr})/count(*)",
			           'last_order_days_ago' => "DATEDIFF(date(NOW()) , date(MAX(created_at)))"
		           ))
		           ->group('customer_id');

		return $collection;
	}

	/**
	 * calculate quartiles
	 *
	 * @param $array
	 * @return array
	 */
    protected function calculateQuartile($array)
    {
        $count = $this->_resultCount;
        if ($count == 0)
            return array(
                "Low" => 0,
                "Medium" => 0,
                "High" => 0
            );

        $first = intval(round(.25 * ($count + 1)));
        $second = intval(round(.50 * ($count + 1)));
        $third = intval(round(.75 * ($count + 1)));

        if (!array_key_exists($first, $array))
            $first = $this->getClosest($first, $array);

        if (!array_key_exists($second, $array))
            $second = $this->getClosest($second, $array);

        if (!array_key_exists($third, $array))
            $third = $this->getClosest($third, $array);

        return array(
            "Low" => $array[$first],
            "Medium" => $array[$second],
            "High" => $array[$third]
        );
    }

	/**
	 * find closest index key from array
	 *
	 * @param $search
	 * @param $arr
	 * @return mix
	 */
	protected function getClosest($search, $arr) {
		$closest = null;
		foreach($arr as $key => $value) {
			if($search == $key)
				return $search;
			if($closest == null || abs($search - $closest) > abs($key - $search)) {
				$closest = $key;
			}
		}
		return $closest;
	}

	/**
	 *  prepare rfm data
	 */
    protected function prepareRfm()
    {
        $collection = $this->getPreparedCollection();
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');

        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array(
                'customer_total_orders' => "count(*)",
            ))->order('customer_total_orders');
        $values = $conn->fetchCol($select);
        $this->_resultCount = count($values);
        $this->rfm[self::FREQUENCY] = $this->calculateQuartile($values);

        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->reset(Zend_Db_Select::ORDER)
            ->columns(array(
                'last_order_days_ago' => "DATEDIFF(date(NOW()) , date(MAX(created_at)))"
            ))->order('last_order_days_ago');
        $values = $conn->fetchCol($select);
        $this->rfm[self::RECENCY] = $this->calculateQuartile($values);

        $expr = $this->_getSalesAmountExpression($collection);
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->reset(Zend_Db_Select::ORDER)
            ->columns(array(
                'customer_average_order_value' => "SUM({$expr})/count(*)",
            ))->order('customer_average_order_value');
        $values = $conn->fetchCol($select);
        $this->rfm[self::MONETARY] = $this->calculateQuartile($values);
    }


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
	 * @return array
	 */
	public function getPreparedRfm($store = 0, $website = 0, $group =0)
	{
		$this->_store = $store;
		$this->_group = $group;
		$this->_website = $website;

		$this->prepareRfm();
		return $this->rfm;
	}
}