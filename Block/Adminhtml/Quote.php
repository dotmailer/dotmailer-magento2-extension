<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Quote extends \Magento\Backend\Block\Widget\Grid\Container
{
	/**
	 * Block constructor
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_blockGroup = 'Dotdigitalgroup_Email';
		$this->_controller = 'adminhtml_quote';
		$this->_headerText = __('Quote');
		parent::_construct();
		$this->buttonList->remove('add');
	}
}

