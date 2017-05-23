<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit;

/**
 * Class Tabs
 * @package Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('ddg_rules_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Exclusion Rule'));
    }
}
