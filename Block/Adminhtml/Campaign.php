<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Campaign.
 */
class Campaign extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Block constructor.
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_campaign';
        $this->_headerText = __('Campaign');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
