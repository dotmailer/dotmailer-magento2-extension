<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Rules extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_rules';
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_headerText = 'Email Exclusion Rule(s)';
        parent::_construct();
        $this->_addButtonLabel = 'Add New Rule';
    }
}
