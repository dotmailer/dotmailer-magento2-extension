<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Importer extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_controller = 'adminhtml_importer';
        $this->_blockGroup = 'ddg_automation';
        $this->_headerText = Mage::helper('ddg')->__('Importer Status');

        $this->_removeButton('add');
    }
}
