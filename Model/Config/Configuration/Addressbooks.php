<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Addressbooks
{
	private function getWebsite()
	{
		$website = Mage::app()->getWebsite();
		$websiteParam = Mage::app()->getRequest()->getParam('website');
		if($websiteParam)
			$website = Mage::app()->getWebsite($websiteParam);
		return $website;
	}

	/**
	 * get address books
	 *
	 * @return null
	 */
	private function getAddressBooks()
	{
		$website = $this->getWebsite();
		$client = Mage::getModel( 'ddg_automation/apiconnector_client' );
		$client->setApiUsername( Mage::helper( 'ddg' )->getApiUsername( $website ) )
			->setApiPassword( Mage::helper( 'ddg' )->getApiPassword( $website ) );

		$savedAddressBooks = Mage::registry( 'addressbooks' );
		//get saved address books from registry
		if ( $savedAddressBooks ) {
			$addressBooks = $savedAddressBooks;
		} else {
			// api all address books
			$addressBooks = $client->getAddressBooks();
			Mage::register( 'addressbooks', $addressBooks );
		}
		return $addressBooks;
	}

	public function toOptionArray()
	{
		$fields = array();

		return $fields;
		$website = $this->getWebsite();

		$enabled = Mage::helper('ddg')->isEnabled($website);

		//get address books options
		if ($enabled) {
			$addressBooks = $this->getAddressBooks();
			//set the error message to the select option
			if ( isset( $addressBooks->message ) ) {
				$fields[] = array( 'value' => 0, 'label' => Mage::helper( 'ddg' )->__( $addressBooks->message) );
			}

			$subscriberAddressBook = Mage::helper('ddg')->getSubscriberAddressBook(Mage::app()->getWebsite());

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
