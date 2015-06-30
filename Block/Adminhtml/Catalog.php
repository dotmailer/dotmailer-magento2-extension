<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Catalog extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

	    $this->_controller = 'adminhtml_catalog';
	    $this->_blockGroup = 'ddg_automation';
	    $this->_headerText = Mage::helper('ddg')->__('Email Catalog');

        $this->_removeButton('add');
    }
}
