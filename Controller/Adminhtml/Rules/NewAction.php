<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class NewAction extends \Magento\Backend\App\AbstractAction
{
    /**
     * Execute method.
     *
     * @return null
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
