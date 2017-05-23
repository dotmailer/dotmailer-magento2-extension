<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

use Magento\Backend\App\Action;

/**
 * Class Review
 * @package Dotdigitalgroup\Email\Controller\Adminhtml
 */
abstract class Review extends Action
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::review');
    }
}
