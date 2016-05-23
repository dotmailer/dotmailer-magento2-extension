<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Wishlist
 *
 * @package Dotdigitalgroup\Email\Block\Adminhtml
 */
class Wishlist extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Block constructor.
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_wishlist';
        $this->_headerText = __('Wishlist');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
