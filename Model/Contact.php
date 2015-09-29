<?php

namespace Dotdigitalgroup\Email\Model;

class Contact extends \Magento\Framework\Model\AbstractModel
{

	const EMAIL_CONTACT_IMPORTED = 1;
	const EMAIL_CONTACT_NOT_IMPORTED = null;
	const EMAIL_SUBSCRIBER_NOT_IMPORTED = null;


	/**
	 * constructor
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Resource\Contact');
	}


	/**
	 * Load contact by customer id
	 * @param $customerId
	 * @return mixed
	 */
	public function loadByCustomerId($customerId)
	{
		$collection =  $this->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setPageSize(1);

		if($collection->count())
			return $collection->getFirstItem();

		return $this;
	}

	/**
	 * get all customer contacts not imported for a website.
	 *
	 * @param $websiteId
	 * @param int $pageSize
	 *
	 */
	public function getContactsToImportForWebsite($websiteId, $pageSize = 100)
	{
		$collection =  $this->getCollection()
                ->addFieldToFilter('website_id', $websiteId)
                ->addFieldToFilter('email_imported', array('null' => true))
                ->addFieldToFilter('customer_id', array('neq' => '0'));


		$collection->getSelect()->limit($pageSize);

		return $collection;
	}

	/**
	 * Get missing contacts.
	 * @param $websiteId
	 * @param int $pageSize
	 * @return mixed
	 */
	public function getMissingContacts($websiteId, $pageSize = 100)
	{
		$collection = $this->getCollection()
		                   ->addFieldToFilter('contact_id', array('null' => true))
		                   ->addFieldToFilter('suppressed', array('null' => true))
		                   ->addFieldToFilter('website_id', $websiteId);

		$collection->getSelect()->limit($pageSize);

		return $collection->load();
	}

	/**
	 * Load Contact by Email.
	 * @param $email
	 * @param $websiteId
	 * @return $this
	 */
	public function loadByCustomerEmail($email, $websiteId)
	{
		$collection = $this->getCollection()
           ->addFieldToFilter('email', $email)
           ->addFieldToFilter('website_id', $websiteId)
           ->setPageSize(1);

		if ($collection->getSize()) {
			return $collection->getFirstItem();
		} else {
			$this->setEmail($email)
			     ->setWebsiteId($websiteId);
		}
		return $this;
	}

	/**
	 * Contact subscribers to import for website
	 * @param $website
	 * @param int $limit
	 *
	 * @return $this
	 */
	public function getSubscribersToImport($website, $limit = 1000)
	{
		$storeIds = $website->getStoreIds();
		$collection = $this->getCollection()
            ->addFieldToFilter('is_subscriber', array('notnull' => true))
			->addFieldToFilter('subscriber_status', '1')
            ->addFieldToFilter('subscriber_imported', array('null' => true))
            ->addFieldToFilter('store_id', array('in' => $storeIds));

		$collection->getSelect()->limit($limit);

		return $collection;
	}

	/**
	 * get all not imported guests for a website.
	 * @param $website
	 *
	 */
	public function getGuests($website)
	{
		$guestCollection = $this->getCollection()
            ->addFieldToFilter('is_guest', array('notnull' => true))
            ->addFieldToFilter('email_imported', array('null' => true))
            ->addFieldToFilter('website_id', $website->getId());
		return $guestCollection->load();
	}

	/**
	 * Number contacts marked as imported.
	 *
	 * @return mixed
	 */
	public function getNumberOfImportedContacs()
	{
		$collection = $this->getCollection()
			->addFieldToFilter('email_imported', array('notnull' => true));

		return $collection->getSize();
	}



	/**
	 * Get the number of customers for a website.
	 * @param int $websiteId
	 *
	 * @return int
	 */
	public function getNumberCustomerContacts($websiteId = 0)
	{
		$countContacts = $this->getCollection()
                 ->addFieldToFilter('customer_id', array('gt' => '0'))
                 ->addFieldToFilter('website_id', $websiteId)
                 ->getSize();
		return $countContacts;
	}

	/**
	 *
	 * Get number of suppressed contacts as customer.
	 * @param int $websiteId
	 *
	 * @return int
	 */
	public function getNumberCustomerSuppressed( $websiteId = 0 )
	{
		$countContacts = $this->getCollection()
             ->addFieldToFilter('customer_id', array('gt' => 0))
             ->addFieldToFilter('website_id', $websiteId)
             ->addFieldToFilter('suppressed', '1')
             ->getSize();

		return $countContacts;
	}

	/**
	 * Get number of synced customers.
	 * @param int $websiteId
	 *
	 * @return int
	 */
	public function getNumberCustomerSynced( $websiteId = 0 )
	{
		$countContacts = $this->getCollection()
             ->addFieldToFilter('customer_id', array('gt' => 0))
             ->addFieldToFilter('website_id', $websiteId)
             ->addFieldToFilter('email_imported' , '1')
             ->getSize();

		return $countContacts;

	}

	/**
	 * Get number of subscribers synced.
	 * @param int $websiteId
	 *
	 * @return int
	 */
	public function getNumberSubscribersSynced( $websiteId = 0 )
	{
		$countContacts = $this->getCollection()
             ->addFieldToFilter('subscriber_status', \Dotdigitalgroup\Email\Model\Newsletter\Subscriber::STATUS_SUBSCRIBED)
             ->addFieldToFilter('subscriber_imported', '1')
             ->addFieldToFilter('website_id', $websiteId)
             ->getSize();

		return $countContacts;
	}

	/**
	 * Get number of subscribers.
	 * @param int $websiteId
	 *
	 * @return int
	 */
	public function getNumberSubscribers( $websiteId = 0 )
	{
		$countContacts = $this->getCollection()
             ->addFieldToFilter('subscriber_status', \Dotdigitalgroup\Email\Model\Newsletter\Subscriber::STATUS_SUBSCRIBED)
             ->addFieldToFilter('website_id', $websiteId)
             ->getSize();
		return $countContacts;
	}

	/**
	 * Reset the imported contacts as guest
	 * @return int
	 */
	public function resetAllGuestContacts()
	{
		$coreResource = $this->_resource;

		$conn = $coreResource->getConnection();

		try {
			$where = array();
			$where[] = $conn->quoteInto('email_imported is ?', new \Zend_Db_Expr('not null'));
			$where[] = $conn->quoteInto('is_guest is ?', new \Zend_Db_Expr('not null'));

			$num = $conn->update($coreResource->getTableName('email_contact'),
				array('email_imported' => new \Zend_Db_Expr('null')),
				$where
			);
		} catch (\Exception $e) {
		}
		return $num;
	}


}