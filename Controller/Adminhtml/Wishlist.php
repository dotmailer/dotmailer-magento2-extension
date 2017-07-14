<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

use Magento\Backend\App\Action;

/**
 * Class Wishlist
 * @package Dotdigitalgroup\Email\Controller\Adminhtml
 */
abstract class Wishlist extends Action
{
    /**
     * @return bool
     */
    protected function _isAllowed() //@codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::wishlist');
    }
}
