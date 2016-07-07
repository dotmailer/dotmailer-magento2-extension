<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Dashboard.
 */
class Dashboard extends \Magento\Backend\Block\Template
{

    /**
     * @var string
     */
    protected $_template = 'dashboard/main.phtml';

    /**
     * Block constructor.
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_dashboard';
        $this->_headerText = __('Dashboard');
        parent::_construct();
        
    }
}
