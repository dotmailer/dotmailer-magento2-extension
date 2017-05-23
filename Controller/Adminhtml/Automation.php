<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

/**
 * Class Automation
 * @package Dotdigitalgroup\Email\Controller\Adminhtml
 */
abstract class Automation extends \Magento\Backend\App\Action
{
    /**
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::automation');
    }
}
