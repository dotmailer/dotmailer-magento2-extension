<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis_Abandonedcarts extends  Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis
{
    protected $_store = 0;
    protected $_group = 0;
    protected $_website = 0;

    /**
     * set template
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::_construct();

        $this->_store = $this->getRequest()->getParam('store');
        $this->_group = $this->getRequest()->getParam('group');
        $this->_website = $this->getRequest()->getParam('website');
        $this->setTemplate('connector/dashboard/tabs/data.phtml');
    }

    /**
     * Prepare the layout.
     *
     * @return Mage_Core_Block_Abstract|void
     * @throws Exception
     */
    protected function _prepareLayout()
    {
        $lifetimeAbanodned = $this->getAbandonedCartInformationForTab();
        $this->addTotal($this->__('Total Abandoned Cart Lost Revenue'), $lifetimeAbanodned->getLifetime());
        $this->addTotal($this->__('Average Abandoned Cart Lost Revenue'), $lifetimeAbanodned->getAverage());
        $this->addTotal($this->__('Total Number Of Abandoned Carts'), $lifetimeAbanodned->getTotalCount(), true);
        $this->addTotal($this->__('Average Abandoned Carts Created Per Day'), $lifetimeAbanodned->getDayCount(), true);
    }

    /**
     * get abandoned cart information for tab from abandoned analysis model
     *
     * @return Varien_Object
     */
    protected function getAbandonedCartInformationForTab()
    {
        $abandonedAnalysisModel = Mage::getModel('ddg_automation/adminhtml_dashboard_tabs_analysis_abandoned');
        return $abandonedAnalysisModel->getLifeTimeAbandoned($this->_store, $this->_website, $this->_group);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return "Abandoned Carts";
    }
}
