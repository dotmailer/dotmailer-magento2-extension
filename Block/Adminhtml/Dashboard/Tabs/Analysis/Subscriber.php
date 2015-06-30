<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis_Subscriber extends  Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis
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
        $lifetimeSubscribers = $this->getSubscriberInformationForTab();
        $this->addTotal($this->__('Total Number Of Subscribers'), $lifetimeSubscribers->getTotalSubscriber(), true);
        $this->addTotal($this->__('Subscribers Who Are Also Customers'), $lifetimeSubscribers->getTotalSubscriberCustomer(), true);
        $this->addTotal($this->__('Average Subscribers Created Per Day'), $lifetimeSubscribers->getSubscribersPerDay(), true);
    }

    /**
     * get subscriber information for tab from subscriber analysis model
     *
     * @return Varien_Object
     */
    protected function getSubscriberInformationForTab()
    {
        $subscriberAnalysisModel = Mage::getModel('ddg_automation/adminhtml_dashboard_tabs_analysis_subscriber');
        return $subscriberAnalysisModel->getLifetimeSubscribers($this->_store, $this->_website, $this->_group);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return "Subscribers";
    }
}
