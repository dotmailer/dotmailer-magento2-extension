<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis_Sales extends  Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis
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
        $lifetimeSales = $this->getSalesInformationForTab();
        $this->addTotal($this->__('Total Sales Revenue'), $lifetimeSales->getLifetime());
        $this->addTotal($this->__('Average Order Value'), $lifetimeSales->getAverage());
        $this->addTotal($this->__('Total Number Of Orders'), $lifetimeSales->getTotalCount(), true);
        $this->addTotal($this->__('Average Orders Created Per Day'), $lifetimeSales->getDayCount(), true);
    }

    /**
     * get sales information from order analysis model
     *
     * @return Varien_Object
     */
    protected function getSalesInformationForTab()
    {
        $orderAnalysisModel = Mage::getModel('ddg_automation/adminhtml_dashboard_tabs_analysis_orders');
        return $orderAnalysisModel->getLifetimeSales($this->_store, $this->_website, $this->_group);
    }

    public function getTitle()
    {
        return "Sales";
    }
}
