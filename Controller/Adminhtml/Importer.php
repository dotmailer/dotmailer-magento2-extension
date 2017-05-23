<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml;

use Magento\Backend\App\Action;

/**
 * Class Importer
 * @package Dotdigitalgroup\Email\Controller\Adminhtml
 */
abstract class Importer extends Action
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::importer');
    }
}
