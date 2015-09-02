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
	/**
	 * Set all imported subscribers for reimport.
	 *
	 * @return int
	 */
	public function resetSubscribers() {

		$conn = $this->getConnection( );

		try {
			$num = $conn->update(
				$conn->getTableName( 'email_contact' ),
				array('subscriber_imported' => new \Zend_Db_Expr( 'null' ) ),
				$conn->quoteInto('subscriber_imported is ?', new \Zend_Db_Expr('not null')));

		} catch ( \Exception $e ) {
		}

		return $num;
	}

	/**
	 * Simulate fresh install.
	 */
	public function resetTables()
	{
		return ;
		//@todo removing the setup module will also remove the extension and will require setup install.
		$conn = $this->getConnection();

		//remove dotmailer code from setup_module table
		$cond = $conn->quoteInto('code = ?', 'Dotdigitalgroup_Email');
		$conn->delete($this->getTableName('setup_module'), $cond);

	}
}