<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Customer;

class Tab
{

    public function getTabLabel()
    {
        return __('Email Activity');
    }

    public function getTabTitle()
    {
        return __('Email Activity');
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