<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_General extends  Mage_Adminhtml_Block_Dashboard_Bar
{
	protected $group = array();

	/**
	 * Set the template.
	 */
	public function __construct()
	{
		$this->initiateGroupArray();
		parent::_construct();
		$this->setTemplate('connector/dashboard/tabs/general/index.phtml');
	}

	/**
	 * Prepare the layout.
	 *
	 * @return Mage_Core_Block_Abstract|void
	 * @throws Exception
	 */
	protected function _prepareLayout()
	{
		$website = 0;
		if ($store = $this->getRequest()->getParam('store')) {
			$website = Mage::app()->getStore($store)->getWebsite();
		} elseif ($this->getRequest()->getParam('website')) {
			$website = $this->getRequest()->getParam('website');
		}
		$apiUsername = Mage::helper('ddg')->getApiUsername($website);
		$apiPassword = Mage::helper('ddg')->getApiPassword($website);
		$data = Mage::getModel('ddg_automation/apiconnector_client')
		            ->setApiUsername($apiUsername)
		            ->setApiPassword($apiPassword)
		            ->getAccountInfo();

        if(isset($data->id))
            $this->prepareGroupArray($data);

		$this->_setChild();

		parent::_prepareLayout();
	}

	protected function _setChild()
	{
		foreach($this->group as $key => $data){
			$this->setChild($key,
				$this->getLayout()->createBlock('ddg_automation/adminhtml_dashboard_tabs_general_data', '', $data)
			);
		}
	}

	protected function prepareGroupArray($data)
	{
		foreach ($data->properties as $one) {
			foreach($this->group as $key => $type){
				if(array_key_exists($one->name, $type)){
					$this->group[$key][$one->name] = $one->value;
				}
			}
		}
	}

	protected function initiateGroupArray()
	{
		$this->group['account'] = array(
			'Title' => 'Account',
			'Name' => $this->__('Not Available'),
			'MainMobilePhoneNumber' => $this->__('Not Available'),
			'MainEmail' => $this->__('Not Available'),
			'AvailableEmailSendsCredits' => $this->__('Not Available')
		);
		$this->group['data'] = array(
			'Title' => 'Data',
			'TransactionalDataAllowanceInMegabytes' => $this->__('Not Available'),
			'TransactionalDataUsageInMegabytes' => $this->__('Not Available')
		);
		$this->group['api'] = array(
			'Title' => 'Api',
			'APILocale' => $this->__('Not Available'),
			'ApiCallsRemaining' => $this->__('Not Available')
		);
	}

	/**
	 * get Tab content title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return "Connector Account Information";
	}

	/**
	 * get column width
	 *
	 * @return string
	 */
	public function getColumnWidth()
	{
		return "400px;";
	}
}
