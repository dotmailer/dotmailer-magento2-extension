<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Contact extends \Magento\Backend\Block\Widget\Grid\Container
{
	/**
	 * Block constructor
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_blockGroup = 'Dotdigitalgroup_Email';
		$this->_controller = 'adminhtml_contact';
		$this->_headerText = __('Contacts');
		parent::_construct();
		$this->buttonList->remove('add');
	}
}
