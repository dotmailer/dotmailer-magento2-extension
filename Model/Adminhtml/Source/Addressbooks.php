<?php

 class Dotdigitalgroup_Email_Model_Adminhtml_Source_Addressbooks
{
	/**
	* Returns the address books options.
	*
	* @return array
	*/
	public function toOptionArray()
	{
        $fields = array();
	    // Add a "Do Not Map" Option
	    $fields[] = array('value' => 0, 'label' => Mage::helper('ddg')->__('-- Please Select --'));
        $website = Mage::app()->getRequest()->getParam('website');

		$enabled = Mage::helper('ddg')->isEnabled($website);

		//get address books options
		if ($enabled) {
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

			//set up fields with book id and label
			foreach ( $addressBooks as $book ) {
				if ( isset( $book->id ) ) {
					$fields[] = array( 'value' => $book->id, 'label' => $book->name );
				}
			}
		}

        return $fields;
    }

}