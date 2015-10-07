<?php

namespace Dotdigitalgroup\Email\Model;


class Proccessor
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
	const IMPORT_TYPE_GUEST = 'Guest';
	const IMPORT_TYPE_QUOTE = 'Quote';
	const IMPORT_TYPE_ORDERS = 'Orders';
	const IMPORT_TYPE_REVIEWS = 'Reviews';
	const IMPORT_TYPE_CONTACT = 'Contact';
	const IMPORT_TYPE_WISHLIST = 'Wishlist';
	const IMPORT_TYPE_SUBSCRIBERS = 'Subscriber';
	const IMPORT_TYPE_CATALOG = 'Catalog_Default';

	private $import_statuses = array(
		'RejectedByWatchdog', 'InvalidFileFormat', 'Unknown',
		'Failed', 'ExceedsAllowedContactLimit', 'NotAvailableInThisVersion'
	);

	protected $_helper;
	protected $_fileHelper;
	protected $_importerFactory;


	public function __construct(
		\Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Dotdigitalgroup\Email\Helper\File $fileHelper,
		\Dotdigitalgroup\Email\Model\Resource\Importer\CollectionFactory $importerCollectionFactory

	){
		$this->_importerFactory = $importerFactory;
		$this->_helper = $helper;
		$this->_fileHelper = $fileHelper;
		$this->importerCollection = $importerCollectionFactory->create();
	}

	/**
	 * register import in queue.
	 *
	 * @param $importType
	 * @param $importData
	 * @param $importMode
	 * @param $websiteId
	 * @param bool|false $file
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function registerQueue($importType, $importData, $importMode, $websiteId, $file = false)
	{
		try {
			$importModel = $this->_importerFactory->create();
			if (!empty($importData)){
				$importData = serialize($importData);
			}
			//filename to be imported
			if ($file)
				$importModel->setImportFile($file);

			//save import data
			$importModel->setImportType($importType)
			     ->setImportData($importData)
			     ->setWebsiteId($websiteId)
			     ->setImportMode($importMode)
			     ->save();

		} catch (\Exception $e) {
			$this->_helper->debug((string)$e, array());
		}
	}

	/**
	 * start point. importer queue processor. check if un-finished import exist.
	 *
	 * @return bool
	 */
	public function processQueue()
	{
		$this->_helper->allowResourceFullExecution();

		if ($item = $this->_getQueue(true)) {
			$websiteId = $item->getWebsiteId();
			if ($this->_helper->isEnabled($websiteId)) {
				$client = $this->_helper->getWebsiteApiClient($websiteId);
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
						$now = gmDate('Y-m-d H:i:s');
						$item->setImportStatus(self::IMPORTED)
						     ->setImportFinished($now)
						     ->setMessage('')
						     ->save();
						$this->_processQueue();

					} elseif (in_array($response->status, $this->import_statuses)) {
						$item->setImportStatus(self::FAILED)
						     ->setMessage($response->status)
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
		//item in queue
		if ($item = $this->_getQueue()) {
			$websiteId = $item->getWebsiteId();

			$client = $this->_helper->getWebsiteApiClient($websiteId);

			$now = gmdate('Y-m-d H:i:s');
			$error = false;

			if ( //import requires file
				$item->getImportType() == self::IMPORT_TYPE_CONTACT or
				$item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS or
				$item->getImportType() == self::IMPORT_TYPE_GUEST
			) {
				if ($item->getImportMode() == self::MODE_CONTACT_DELETE) {
					//remove from account
					$client = $this->_helper->getWebsiteApiClient($websiteId);
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
						$addressbook = $this->_helper->getCustomerAddressBook($websiteId);
					if ($item->getImportType() == self::IMPORT_TYPE_SUBSCRIBERS)
						$addressbook = $this->_helper->getSubscriberAddressBook($websiteId);
					if ($item->getImportType() == self::IMPORT_TYPE_GUEST)
						$addressbook = $this->_helper->getGuestAddressBook($websiteId);

					$file = $item->getImportFile();
					if (!empty($file) && !empty($addressbook)) {
						$result = $client->postAddressBookContactsImport($file, $addressbook);

						if (isset($result->message) && !isset($result->id)) {
							$error = true;
						}
					}
				}
			} elseif ($item->getImportMode() == self::MODE_SINGLE_DELETE) { //import to single delete
				$importData = unserialize($item->getImportData());
				$result = $client->deleteContactsTransactionalData($importData->id, $item->getImportType());
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
	 * get queue items from importer.
	 *
	 * @param bool|false $importing
	 *
	 * @return bool
	 */
	private function _getQueue($importing = false)
	{
		//reset collection, using same collection multiple times before load.
		$this->importerCollection->reset();
		//if true then return item with importing status
		if ($importing)
			$this->importerCollection->addFieldToFilter('import_status', array('eq' => self::IMPORTING));
		else
			$this->importerCollection->addFieldToFilter('import_status', array('eq' => self::NOT_IMPORTED));

		$this->importerCollection->setPageSize(1);

		if ($this->importerCollection->count()) {
			return $this->importerCollection->getFirstItem();
		}
		return false;
	}
}