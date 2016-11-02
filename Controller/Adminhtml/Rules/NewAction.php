<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class NewAction extends \Magento\Backend\App\AbstractAction
{
    /**
     * Execute method.
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
