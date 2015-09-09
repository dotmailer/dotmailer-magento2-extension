<?php

namespace Dotdigitalgroup\Email\Model\Resource;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime as LibDateTime;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product;

class Catalog extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('email_catalog', 'id');
	}


	/**
	 * Reset for re-import.
	 *
	 * @return int
	 */
	public function resetCatalog()
	{

		$conn = $this->getConnection();
		try{
			$num = $conn->update($conn->getTableName('email_catalog'),
				array('imported' => new \Zend_Db_Expr('null'), 'modified' => new \Zend_Db_Expr('null'))
			);
		}catch (\Exception $e){

		}
		return $num;
	}

}