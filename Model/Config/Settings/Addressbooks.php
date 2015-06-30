<?php

namespace Dotdigitalgroup\Email\Model\Config\Settings;


class Addressbooks implements \Magento\Framework\Option\ArrayInterface
{
	/**
	 * options
	 *
	 * @var array
	 */
	protected $_options = null;

	/**
	 * Configuration structure
	 *
	 * @var \Magento\Config\Model\Config\Structure
	 */
	protected $_configStructure;

	protected $logger;

	/**
	 * @param \Magento\Config\Model\Config\Structure $configStructure
	 */
	public function __construct(\Magento\Config\Model\Config\Structure $configStructure,
		\Psr\Log\LoggerInterface $logger
	)
	{
		$this->_configStructure = $configStructure;
		$this->logger = $logger;
	}

	/**
	 * Retrieve list of options
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$fields = array();
		// Add a "Do Not Map" Option
		$fields[] = array('value' => 0, 'label' => '-- Please Select --');
		//$website = Mage::app()->getRequest()->getParam('website');
		return $fields;


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
