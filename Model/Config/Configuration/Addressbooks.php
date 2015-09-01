<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Addressbooks
{

	protected $_objectManager;
	protected $_helper;
	protected $_registry;
	protected $_storeManager;

	public function __construct(
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_objectManager = $objectManagerInterface;
		$this->_storeManager = $storeManagerInterface;
		$this->_helper = $data;
		$this->_registry = $registry;

	}

	/**
	 * get address books
	 *
	 * @return null
	 */
	private function getAddressBooks()
	{
		$website = $this->_helper->getWebsite();
		$client = $this->_helper->getWebsiteApiClient($website);

		$savedAddressBooks = $this->_registry->registry( 'addressbooks' );
		//get saved address books from registry
		if ( $savedAddressBooks ) {
			$addressBooks = $savedAddressBooks;
		} else {
			// api all address books
			$addressBooks = $client->getAddressBooks();
			$this->_registry->register( 'addressbooks', $addressBooks );
		}
		return $addressBooks;
	}

	public function toOptionArray()
	{
		$fields = array();
		$website = $this->_helper->getWebsite();
		$apiEnabled = $this->_helper->isEnabled($website);

		//get address books options
		if ($apiEnabled) {
			$addressBooks = $this->getAddressBooks();
			//set the error message to the select option
			if ( isset( $addressBooks->message ) ) {
				$fields[] = array( 'value' => 0, 'label' => __( $addressBooks->message) );
			}

			$subscriberAddressBook = $this->_helper->getSubscriberAddressBook($this->_helper->getWebsite());

			//set up fields with book id and label
			foreach ( $addressBooks as $book ) {
				if (isset($book->id) &&  $book->visibility == 'Public' &&  $book->id != $subscriberAddressBook) {
					$fields[] = array( 'value' => $book->id, 'label' => $book->name );
				}
			}
		}

		return $fields;
	}
}
