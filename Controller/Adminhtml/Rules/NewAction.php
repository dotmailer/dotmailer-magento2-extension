<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class NewAction extends Action implements HttpGetActionInterface
{
    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
