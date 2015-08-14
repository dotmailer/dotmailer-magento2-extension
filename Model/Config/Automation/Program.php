<?php

namespace Dotdigitalgroup\Email\Model\Config\Automation;

class Program implements \Magento\Framework\Option\ArrayInterface
{

	public function __construct(
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Model\Apiconnector\Rest $rest
	)
	{
		$this->rest = $rest;
		$this->_registry = $registry;
	}

	public function toOptionArray()
	{
		$fields = array();

		//@todo get the website id

		$fields[] = array('value' => '0', 'label' => '-- Disabled --');

		//@todo check if api is enabled


		$savedPrograms = $this->_registry->registry('programs');

		//get saved datafileds from registry
		if ( $savedPrograms ) {
			$programs = $savedPrograms;
		} else {
			//grab the datafields request and save to register
			$programs = $this->rest->getPrograms();
			$this->_registry->register('programs', $programs);
		}

		//set the api error message for the first option
		if ( isset( $programs->message ) ) {
			//message
			$fields[] = array( 'value' => 0, 'label' => $programs->message);
		} else {
			//loop for all programs option
			foreach ( $programs as $program ) {
				if ( isset( $program->id ) && $program->status == 'Active' ) {
					$fields[] = array(
						'value' => $program->id,
						'label' => $program->name
					);
				}
			}
		}


		return $fields;
	}

}