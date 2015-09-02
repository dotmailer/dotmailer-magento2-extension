<?php

namespace Dotdigitalgroup\Email\Model\Resource;

use Magento\Framework\Stdlib\DateTime as LibDateTime;

class Wishlist extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
	/**
	 * Initialize resource
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('email_wishlist', 'id');
	}

	/**
	 * Reset the email reviews for reimport.
	 *
	 * @return int
	 */
	public function resetWishlists()
	{
		$conn = $this->getConnection('core_write');
		try{
			$num = $conn->update($conn->getTableName('email_wishlist'),
				array('wishlist_imported' => new \Zend_Db_Expr('null'), 'wishlist_modified' => new \Zend_Db_Expr('null'))
			);
		}catch (\Exception $e){
		}

		return $num;
	}

}