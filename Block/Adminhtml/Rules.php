<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Rules extends \Magento\Backend\Block\Widget\Container
{
    public function __construct(
	    \Magento\Backend\Block\Widget\Context $context
	    )
    {
        parent::__construct($context);

	    $this->_controller = 'adminhtml_rules';
	    $this->_blockGroup = 'dotdigitalgroup_email';
	    $this->_headerText = 'Email Exclusion Rule(s)';
        $this->_addButtonLabel = 'Add New Rule';
    }
}
