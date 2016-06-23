<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class Order extends Action
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::order');
    }
}
