<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Catalog extends \Magento\Backend\Block\Widget\Grid\Container
{
	/**
	 * Block constructor
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_blockGroup = 'Dotdigitalgroup_Email';
		$this->_controller = 'adminhtml_catalog';
		$this->_headerText = __('Catalogs');
		parent::_construct();
		$this->buttonList->remove('add');
	}
}
