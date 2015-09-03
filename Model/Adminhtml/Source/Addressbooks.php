<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source;

 class Addressbooks
{
	 protected $_helper;
	 protected $_registry;
	 protected $_request;

	public function __construct(
		 \Magento\Framework\Registry $registry,
		 \Magento\Framework\App\RequestInterface $requestInterface,
		 \Dotdigitalgroup\Email\Helper\Data $helper
	)
	{
		$this->_helper  = $helper;
		$this->_registry = $registry;
		$this->_request = $requestInterface;
	}
	/**
	* Returns the address books options.
	*
	* @return array
	*/
	public function toOptionArray()
	{
        $fields = array();
	    // Add a "Do Not Map" Option
	    $fields[] = array('value' => 0, 'label' => __('-- Please Select --'));
        $website = $this->_request->getParam('website');

		$apiEnabled = $this->_helper->isEnabled($website);

		//get address books options
		if ($apiEnabled) {
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