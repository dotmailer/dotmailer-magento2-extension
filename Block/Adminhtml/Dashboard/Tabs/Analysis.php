<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis extends  Mage_Adminhtml_Block_Dashboard_Bar
{
	/**
	 * set template
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		parent::_construct();
		$this->setTemplate('connector/dashboard/tabs/analysis/index.phtml');
	}

	/**
	 * Prepare the layout. set child blocks
	 *
	 * @return Mage_Core_Block_Abstract|void
	 * @throws Exception
	 */
	protected function _prepareLayout()
	{
		$this->setChild('sales',
			$this->getLayout()->createBlock('ddg_automation/adminhtml_dashboard_tabs_analysis_sales')
		);
		$this->setChild('abandoned_cart',
			$this->getLayout()->createBlock('ddg_automation/adminhtml_dashboard_tabs_analysis_abandonedcarts')
		);
		$this->setChild('customer',
			$this->getLayout()->createBlock('ddg_automation/adminhtml_dashboard_tabs_analysis_customer')
		);
		$this->setChild('subscriber',
			$this->getLayout()->createBlock('ddg_automation/adminhtml_dashboard_tabs_analysis_subscriber')
		);
		$this->setChild('rfm',
			$this->getLayout()->createBlock('ddg_automation/adminhtml_dashboard_tabs_analysis_rfm')
		);
		parent::_prepareLayout();
	}

	/**
	 * get Tab content title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return "Marketing Automation Data Analysis";
	}

	/**
	 * get column width
	 *
	 * @return string
	 */
	public function getColumnWidth()
	{
		return "290px";
	}
}
