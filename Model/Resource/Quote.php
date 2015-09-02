<?php

namespace Dotdigitalgroup\Email\Model\Resource;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime as LibDateTime;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product;

class Quote extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('email_quote', 'id');
	}


	/**
	 * Reset the email quote for reimport.
	 *
	 * @return int
	 */
	public function resetQuotes()
	{
		$conn = $this->getConnection();
		try{
			$num = $conn->update($conn->getTableName('email_quote'),
				array('imported' => new \Zend_Db_Expr('null'), 'modified' => new \Zend_Db_Expr('null'))
			);
		}catch (\Exception $e){
		}

		return $num;
	}

}