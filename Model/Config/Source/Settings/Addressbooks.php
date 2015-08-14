<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Settings;


use Dotdigitalgroup\Email\Model\Apiconnector\Rest;

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


	/**
	 * @param \Magento\Config\Model\Config\Structure $configStructure
	 */
	public function __construct(
		\Magento\Framework\Registry $registry,
		\Magento\Config\Model\Config\Structure $configStructure

	)
	{
		$this->_registry = $registry;
		$this->_configStructure = $configStructure;
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


		//@todo get websites level
		//@todo check if the sync is enabled
		//if ($enabled) {

		$addressBooks = $this->_registry->registry('addressbooks');

		if ($addressBooks) {
			$address = $addressBooks;
		} else {
			$rest = new Rest();
			//make an api call an register the addressbooks
			$addressBooks = $rest->getAddressBooks();
			if ($addressBooks)
				$this->_registry->register('addressbooks', $addressBooks);
		}

		//set up fields with book id and label
		foreach ( $addressBooks as $book ) {
			if ( isset( $book->id ) ) {
				$fields[] = array(
					'value' => $book->id,
					'label' => $book->name );
			}
		}


		return $fields;
	}
}
