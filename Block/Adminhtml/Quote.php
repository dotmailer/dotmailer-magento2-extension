<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Quote extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

	    $this->_controller = 'adminhtml_quote';
	    $this->_blockGroup = 'ddg_automation';
	    $this->_headerText = Mage::helper('ddg')->__('Email Quote(s)');

        $this->_removeButton('add');
    }
}
