<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard extends  Mage_Adminhtml_Block_Dashboard_Bar

{
	/**
	 * Set the template.
	 */
	public function __construct()
	{
		parent::_construct();

		$this->setTemplate('connector/dashboard/accountbar.phtml');
	}

	/**
	 * Prepare the layout.
	 *
	 * @return Mage_Core_Block_Abstract|void
	 * @throws Exception
	 */
	protected function _prepareLayout() {


		$website = 0;
		//request store param
		if ($store = $this->getRequest()->getParam('store')) {
			$website = Mage::app()->getStore($store)->getWebsite();
			//website param
		} elseif ($this->getRequest()->getParam('website')) {
			$website = $this->getRequest()->getParam('website');
		}

		$apiUsername = Mage::helper('ddg')->getApiUsername($website);
		$apiPassword = Mage::helper('ddg')->getApiPassword($website);

		//api get account info
		$data = Mage::getModel('ddg_automation/apiconnector_client')
		            ->setApiUsername($apiUsername)
		            ->setApiPassword($apiPassword)
		            ->getAccountInfo();

		//check if properties for the data exists
		if (isset($data->properties)) {
			foreach ( $data->properties as $one ) {
				//add total for the api calls
				$this->addTotal( $this->__( $one->name ), $one->value, true );
			}
		}
	}

}
