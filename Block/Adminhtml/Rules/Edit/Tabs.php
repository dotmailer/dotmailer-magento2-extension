<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Rules_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('ddg_rules_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('ddg')->__('Exclusion Rule'));
    }
}
