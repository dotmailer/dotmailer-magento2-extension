<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

abstract class Automation extends \Magento\Backend\App\Action
{
    /**
     * @return bool
     */
    public function _isAllowed() //@codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::automation');
    }
}
