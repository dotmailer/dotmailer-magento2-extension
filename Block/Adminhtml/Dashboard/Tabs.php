<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

	public function __construct()
	{
		parent::__construct();
		$this->setId('connector_dashboard_tabs');
		$this->setTitle($this->__('Dashboards' ));
		$this->setTemplate('connector/dashboard/tabs.phtml');
	}

	/**
	 * set tabs
	 *
	 * @return Mage_Core_Block_Abstract
	 * @throws Exception
	 */
	protected function _beforeToHtml()
	{
		$this->addTab(
			'general',
			array (
				'label' => $this->__('Account Information'),
				'title' => $this->__('Account Information'),
				'content' => $this->getLayout()->createBlock('ddg_automation/adminhtml_dashboard_tabs_general')->toHtml(),
			)
		);
		$this->addTab(
			'status',
			array (
				'label' => $this->__('Connector Status'),
				'title' => $this->__('Connector Status'),
				'url'   => $this->getUrl('*/*/statusGrid'),
				'class' => 'ajax',
				'active' => true
			)
		);
		$this->addTab(
			'analysis',
			array (
				'label' => $this->__('Data Analysis'),
				'title' => $this->__('Data Analysis'),
				'content' => $this->getLayout()->createBlock('ddg_automation/adminhtml_dashboard_tabs_analysis')->toHtml()
			)
		);
		return parent::_beforeToHtml();
	}
}
