<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Rules extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

	    $this->_controller = 'adminhtml_rules';
	    $this->_blockGroup = 'ddg_automation';
	    $this->_headerText = Mage::helper('ddg')->__('Email Exclusion Rule(s)');
        $this->_addButtonLabel = Mage::helper('ddg')->__('Add New Rule');
        parent::__construct();
    }
}
