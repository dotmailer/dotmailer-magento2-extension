<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Cron extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Block constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_cron';
        $this->_headerText = __('Cron');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
