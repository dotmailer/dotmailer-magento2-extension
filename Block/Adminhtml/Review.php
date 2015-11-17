<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Review extends \Magento\Backend\Block\Widget\Grid\Container
{
	/**
	 * Block constructor
	 *
	 * @return void
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

