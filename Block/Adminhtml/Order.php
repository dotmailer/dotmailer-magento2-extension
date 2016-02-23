<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Order extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Block constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_order';
        $this->_headerText = __('Order');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}

