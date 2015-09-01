<?php

namespace Dotdigitalgroup\Email\Model\Resource;


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
	 * Remove all contact_id from the table.
	 * @return int
	 *
	 */
	public function deleteContactIds()
	{

		$conn = $this->getConnection();
		try{
			$num = $conn->update($this->getTable('email_contact'),
				array('contact_id' => new \Zend_Db_Expr('null')),
				$conn->quoteInto('contact_id is ?', new \Zend_Db_Expr('not null'))
			);
		}catch (\Exception $e){

		}
		return $num;
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