<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Studio;

class Index extends \Magento\Backend\App\AbstractAction
{
    /**
     * Execute method.
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
