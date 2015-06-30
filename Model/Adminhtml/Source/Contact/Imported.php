<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Contact_Imported
{
	/**
	 * Contact imported options.
	 *
	 * @return array
	 */
	public function getOptions()
    {
        return array(
            '1' =>  Mage::helper('ddg')->__('Imported'),
            'null' => Mage::helper('ddg')->__('Not Imported'),
        );
    }
}