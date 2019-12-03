<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

class Index extends \Magento\Newsletter\Controller\Manage
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Newsletter Subscription'));
        $this->_view->renderLayout();
    }
}