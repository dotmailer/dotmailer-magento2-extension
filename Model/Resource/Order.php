<?php

namespace Dotdigitalgroup\Email\Model\Resource;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime as LibDateTime;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product;

class Order extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('email_order', 'email_order_id');
	}


	/**
	 * Reset the email order for reimport.
	 *
	 * @return int
	 */
	public function resetOrders()
	{
		$conn = $this->getConnection();
		try{
			$num = $conn->update($conn->getTableName('email_order'),
				array('email_imported' => new \Zend_Db_Expr('null'), 'modified' => new \Zend_Db_Expr('null')),
				$conn->quoteInto('email_imported is ?', new \Zend_Db_Expr('not null'))
			);
		}catch (\Exception $e){
		}

		return $num;
	}

}