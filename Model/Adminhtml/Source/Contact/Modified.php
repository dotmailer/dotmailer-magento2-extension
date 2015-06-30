<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Contact_Modified
{
	/**
	 * Contact imported options.
	 *
	 * @return array
	 */
	public function getOptions()
    {
        return array(
            '1' =>  Mage::helper('ddg')->__('Modified'),
            'null' => Mage::helper('ddg')->__('Not Modified'),
        );
    }
}