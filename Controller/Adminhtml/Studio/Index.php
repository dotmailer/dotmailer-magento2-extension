<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Studio;

class Index extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::studio';

    /**
     * Execute method.
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Engagement Cloud'));
        $this->_view->renderLayout();
    }
}
