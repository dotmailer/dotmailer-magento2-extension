<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Datamapping_Datafieldaccess
{
	/**
	 * @return array
	 */
	public function toOptionArray()
	{
		$dataType = array(
			array('value' => 'Private', 'label' => Mage::helper('ddg')->__('Private')),
            array('value' => 'Public',  'label' => Mage::helper('ddg')->__('Public')),
		);

		return $dataType;
	}
}