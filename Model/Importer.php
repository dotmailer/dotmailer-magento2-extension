<?php

namespace Dotdigitalgroup\Email\Model;

class Importer extends \Magento\Framework\Model\AbstractModel
{
	//import statuses
	const NOT_IMPORTED = 0;
	const IMPORTING = 1;
	const IMPORTED = 2;
	const FAILED = 3;

	//import mode
	const MODE_BULK = 'Bulk';
	const MODE_SINGLE = 'Single';
	const MODE_SINGLE_DELETE = 'Single_Delete';
	const MODE_CONTACT_DELETE = 'Contact_Delete';

	//import type
	const IMPORT_TYPE_CONTACT = 'Contact';
	const IMPORT_TYPE_ORDERS = 'Orders';
	const IMPORT_TYPE_WISHLIST = 'Wishlist';
	const IMPORT_TYPE_REVIEWS = 'Reviews';
	const IMPORT_TYPE_CATALOG = 'Catalog_Default';
	const IMPORT_TYPE_QUOTE = 'Quote';
	const IMPORT_TYPE_SUBSCRIBERS = 'Subscriber';
	const IMPORT_TYPE_GUEST = 'Guest';

	private $import_statuses = array(
		'RejectedByWatchdog', 'InvalidFileFormat', 'Unknown',
		'Failed', 'ExceedsAllowedContactLimit', 'NotAvailableInThisVersion'
	);

	/**
	 * constructor
	 */
	public function _construct()
	{
		$this->_init('Dotdigitalgroup\Email\Model\Resource\Importer');
	}


	/**
	 * register import in queue
	 *
	 * @param $importType
	 * @param $importData
	 * @param $importMode
	 * @param $websiteId
	 * @param bool $file
	 * @return bool
	 */
	public function registerQueue($importType, $importData, $importMode, $websiteId, $file = false)
	{
		try {
			if (!empty($importData))
				$importData = serialize($importData);

			if ($file)
				$this->setImportFile($file);

			$this->setImportType($importType)
			     ->setImportData($importData)
			     ->setWebsiteId($websiteId)
			     ->setImportMode($importMode)
			     ->save();

			return true;
		} catch (\Exception $e) {
			$this->_logger->$e->getMessage();
		}
	}

	/**
	 * start point. importer queue processor. check if un-finished import exist.
	 *
	 * @return bool
	 */
	public function processQueue()
	{
		$helper = Mage::helper('ddg');
		$helper->allowResourceFullExecution();
		if ($item = $this->_getQueue(true)) {
			$websiteId = $item->getWebsiteId();
			$enabled = $helper->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);
			if ($enabled) {
				$client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
				if (
					$item->getImportType() == self::IMPORT_TYPE_CONTACT or
					$item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
					$item->getImportType() == self::IMPORT_TYPE_GUEST

				) {
					$response = $client->getContactsImportByImportId($item->getImportId());
				} else {
					$response = $client->getContactsTransactionalDataImportByImportId($item->getImportId());
				}
				if ($response && !isset($response->message)) {
					if ($response->status == 'Finished') {
						$now = Mage::getSingleton('core/date')->gmtDate();
						$item->setImportStatus(self::IMPORTED)
						     ->setImportFinished($now)
						     ->setMessage('')
						     ->save();

						$this->_processQueue();
					} elseif (in_array($response->status, $this->import_statuses)) {
						$item->setImportStatus(self::FAILED)
						     ->setMessage($response->message)
						     ->save();

						$this->_processQueue();
					}
				}
				if ($response && isset($response->message)) {
					$item->setImportStatus(self::FAILED)
					     ->setMessage($response->message)
					     ->save();

					$this->_processQueue();
				}
			}
		} else {
			$this->_processQueue();
		}
		return true;
	}

	/**
	 * actual importer queue processor
	 */
	private function _processQueue()
	{
		if ($item = $this->_getQueue()) {
			$helper = Mage::helper('ddg');
			$websiteId = $item->getWebsiteId();
			$client = $helper->getWebsiteApiClient($websiteId);
			$now = Mage::getSingleton('core/date')->gmtDate();
			$error = false;

			if ( //import requires file
				$item->getImportType() == self::IMPORT_TYPE_CONTACT or
				$item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
				$item->getImportType() == self::IMPORT_TYPE_GUEST
			) {
				if ($item->getImportMode() == self::MODE_CONTACT_DELETE) {
					//remove from account
					$client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
					$email = unserialize($item->getImportData());
					$apiContact = $client->postContacts($email);
					if (!isset($apiContact->message) && isset($apiContact->id)) {
						$result = $client->deleteContact($apiContact->id);
						if (isset($result->message)) {
							$error = true;
						}
					} elseif (isset($apiContact->message) && !isset($apiContact->id)) {
						$error = true;
						$result = $apiContact;
					}
				} else {
					//address book
					$addressbook = '';
					if ($item->getImportType() == self::IMPORT_TYPE_CONTACT)
						$addressbook = $helper->getCustomerAddressBook($websiteId);
					if ($item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS)
						$addressbook = $helper->getSubscriberAddressBook($websiteId);
					if ($item->getImportType() == self::IMPORT_TYPE_GUEST)
						$addressbook = $helper->getGuestAddressBook($websiteId);

					$file = $item->getImportFile();
					if (!empty($file) && !empty($addressbook)) {
						$result = $client->postAddressBookContactsImport($file, $addressbook);
						$fileHelper = Mage::helper('ddg/file');
						if (isset($result->message) && !isset($result->id)) {
							$error = true;
						} else
							$fileHelper->archiveCSV($file);
					}
				}
			} elseif ($item->getImportMode() == self::MODE_SINGLE_DELETE) { //import to single delete
				$importData = unserialize($item->getImportData());
				$result = $client->deleteContactsTransactionalData($importData[0], $item->getImportType());
				if (isset($result->message)) {
					$error = true;
				}
			} else {
				$importData = unserialize($item->getImportData());
				//catalog type and bulk mode
				if (strpos($item->getImportType(), 'Catalog_') !== false && $item->getImportMode() == self::MODE_BULK) {
					$result = $client->postAccountTransactionalDataImport($importData, $item->getImportType());
					if (isset($result->message) && !isset($result->id)) {
						$error = true;
					}
				} elseif ($item->getImportMode() == self::MODE_SINGLE) { // single contact import
					$result = $client->postContactsTransactionalData($importData, $item->getImportType());
					if (isset($result->message)) {
						$error = true;
					}
				} else { //bulk import transactional data
					$result = $client->postContactsTransactionalDataImport($importData, $item->getImportType());
					if (isset($result->message) && !isset($result->id)) {
						$error = true;
					}
				}
			}
			if (!$error) {
				if ($item->getImportMode() == self::MODE_SINGLE_DELETE or
				    $item->getImportMode() == self::MODE_SINGLE or
				    $item->getImportMode() == self::MODE_CONTACT_DELETE
				) {
					$item->setImportStatus(self::IMPORTED)
					     ->setImportFinished($now)
					     ->setImportStarted($now)
					     ->save();
				}
				elseif(isset($result->id)){
					$item->setImportStatus(self::IMPORTING)
					     ->setImportId($result->id)
					     ->setImportStarted($now)
					     ->save();
				}
				else{
					$item->setImportStatus(self::FAILED)
					     ->setMessage($result->message)
					     ->save();
				}
			} elseif ($error) {
				$item->setImportStatus(self::FAILED)
				     ->setMessage($result->message)
				     ->save();
			}
		}
	}

	/**
	 * get queue items from importer
	 *
	 * @param bool $importing
	 * @return bool|Varien_Object
	 */
	private function _getQueue($importing = false)
	{
		$collection = $this->getCollection();

		//if true then return item with importing status
		if ($importing)
			$collection->addFieldToFilter('import_status', array('eq' => self::IMPORTING));
		else
			$collection->addFieldToFilter('import_status', array('eq' => self::NOT_IMPORTED));

		$collection->setPageSize(1);
		if ($collection->count()) {
			return $collection->getFirstItem();
		}
		return false;
	}
}