<?php
namespace Dotdigitalgroup\EmailBlock\Adminhtml\Contact\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{

	public function _construct()
	{
		parent::_construct();
		$this->setId('contact_record');
		$this->setDestElementId('edit_form');
		$this->setTitle(__('Contact Information'));
	}
}