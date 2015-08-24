<?php

namespace Dotdigitalgroup\Email\Model\Resource;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime as LibDateTime;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\Product;

class Contact extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('email_contact', 'email_contact_id');
	}




	/**
	 * Reset the imported contacts
	 * @return int
	 */
	public function resetAllContacts()
	{
		try{
			$conn = $this->getConnection();
			$num = $conn->update($conn->getTableName('email_contact'),
				array('email_imported' => new \Zend_Db_Expr('null')),
				$conn->quoteInto('email_imported is ?', new \Zend_Db_Expr('not null'))
			);
		}catch (\Exception $e){
		}

		return $num;
	}

}