<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Customer_Tab
    extends Mage_Adminhtml_Block_Widget
    implements Mage_Adminhtml_Block_Widget_Tab_Interface {

    public function getTabLabel()
    {
        return $this->__('Email Activity');
    }

    public function getTabTitle()
    {
        return $this->__('Email Activity');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    public function getTabUrl()
    {
        return $this->getUrl('*/customer/stat', array('_current' => true));
    }

    public function getTabClass()
    {
        return 'ajax';
    }

    public function getAfter()
    {
        return 'tags';
    }
}