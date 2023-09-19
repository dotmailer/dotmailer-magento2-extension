<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

class Index extends \Magento\Newsletter\Controller\Manage
{
    /**
     * Execute.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Newsletter Subscription'));
        $this->_view->renderLayout();
    }
}
