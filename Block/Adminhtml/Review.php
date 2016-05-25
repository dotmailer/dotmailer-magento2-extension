<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Review.
 */
class Review extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Block constructor.
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_review';
        $this->_headerText = __('Review');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
