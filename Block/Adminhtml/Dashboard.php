<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Dashboard block
 *
 * @api
 */
class Dashboard extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var string
     */
    public $_template = 'dashboard/main.phtml';

    /**
     * Dashboard constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_dashboard';
        $this->_headerText = __('Dashboard');
        parent::_construct();
    }
}
