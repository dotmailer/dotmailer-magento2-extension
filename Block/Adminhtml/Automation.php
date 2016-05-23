<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Automation
 *
 * @package Dotdigitalgroup\Email\Block\Adminhtml
 */
class Automation extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Block constructor.
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_automation';
        $this->_headerText = __('Automation');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
