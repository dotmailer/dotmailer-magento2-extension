<?php

namespace Dotdigitalgroup\Email\Model;

class Order extends \Magento\Framework\Model\AbstractModel
{
    const EMAIL_ORDER_NOT_IMPORTED = null;
    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Dotdigitalgroup\Email\Model\Resource\Order');
    }


    /**
     * Load the email order by quote id.
     * @param $orderId
     * @param $quoteId
     * @return $this|Varien_Object
     */
    public function loadByOrderId($orderId, $quoteId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId)
            ->setPageSize(1);

        if ($collection->count()) {
            return $collection->getFirstItem();
        } else {
            $this->setOrderId($orderId)
                ->setQuoteId($quoteId);
        }
        return $this;
    }


	/**
	 * @param $orderId
	 * @param $quoteId
	 * @param $storeId
	 *
	 * @return $this|Varien_Object
	 */
	public function getEmailOrderRow($orderId, $quoteId, $storeId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('store_id', $storeId);

        if ($collection->count()) {
            return $collection->getFirstItem();
        } else {
            $now = Mage::getSingleton('core/date')->gmtDate();

            $this->setOrderId($orderId)
                ->setQuoteId($quoteId)
                ->setStoreId($storeId)
                ->setCreatedAt($now);
        }
        return $this;

    }

	/**
	 * Get all orders with particular status within certain days.
	 *
	 * @param $storeIds
	 * @param $limit
	 * @param $orderStatuses
	 * @param bool|false $modified
	 *
	 * @return $this
	 */
    public function getOrdersToImport($storeIds, $limit, $orderStatuses, $modified = false)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->addFieldToFilter('order_status', array('in' => $orderStatuses));

        if ($modified) {
            $collection
                ->addFieldToFilter('email_imported', 1)
                ->addFieldToFilter('modified', 1);
        } else
            $collection->addFieldToFilter('email_imported', array('null' => true));


        $collection->getSelect()->limit($limit);
        return $collection;
    }

    /**
     * Get all sent orders older then certain days.
     *
     * @param $storeIds
     * @param $limit
     *
     * @return Dotdigitalgroup_Email_Model_Resource_Order_Collection
     */
    public function getAllSentOrders($storeIds, $limit)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email_imported', 1)
            ->addFieldToFilter('store_id', array('in' => $storeIds));

        $collection->getSelect()->limit($limit);
        return $collection->load();
    }
    
	/**
	 * Reset the email order for reimport.
	 *
	 * @return int
	 */
	public function resetOrders()
	{
		/** @var $coreResource Mage_Core_Model_Resource */
		$coreResource = Mage::getSingleton('core/resource');

		/** @var $conn Varien_Db_Adapter_Pdo_Mysql */
		$conn = $coreResource->getConnection('core_write');
		try{
			$num = $conn->update($coreResource->getTableName('ddg_automation/order'),
				array('email_imported' => new Zend_Db_Expr('null'), 'modified' => new Zend_Db_Expr('null')),
				$conn->quoteInto('email_imported is ?', new Zend_Db_Expr('not null'))
			);
		}catch (Exception $e){
			Mage::logException($e);
		}

		return $num;
	}

}