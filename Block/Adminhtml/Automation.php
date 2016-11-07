<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Automation.
 */
class Automation extends \Magento\Backend\Block\Widget\Grid\Container
{

    public function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_automation';
        $this->_headerText = __('Automation');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
