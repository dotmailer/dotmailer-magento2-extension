<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis_Rfm extends  Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_Analysis
{
    protected $rfm = array();
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
        $this->setTemplate('connector/dashboard/tabs/analysis/rfm.phtml');
    }

    /**
     * Prepare the layout.
     *
     * @return Mage_Core_Block_Abstract|void
     * @throws Exception
     */
    protected function _prepareLayout()
    {
        $rfmAnalysisModel = Mage::getModel('ddg_automation/adminhtml_dashboard_tabs_analysis_rfm');
        $this->rfm = $rfmAnalysisModel->getPreparedRfm($this->_store, $this->_website, $this->_group);
        foreach($this->rfm['Monetary'] as $key => $value)
        {
            $this->rfm['Monetary'][$key] = $this->format($value);
        }
    }

    /**
     * @return array
     */
    protected function getRfm()
    {
        foreach($this->rfm as $k => $type){
            foreach($type as $p => $value){
                if($value == '')
                    $this->rfm[$k][$p] = '0';
            }
        }
        return $this->rfm;
    }

    /**
     * get currency
     *
     * @return Mage_Directory_Model_Currency
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function getCurrency()
    {
        if ($this->_store) {
            $currencyCode = Mage::app()->getStore($this->getRequest()->getParam('store'))->getBaseCurrency();
        } else if ($this->_website){
            $currencyCode = Mage::app()->getWebsite($this->getRequest()->getParam('website'))->getBaseCurrency();
        } else if ($this->_group){
            $currencyCode =  Mage::app()->getGroup($this->getRequest()->getParam('group'))->getWebsite()->getBaseCurrency();
        } else {
            $currencyCode = Mage::app()->getStore()->getBaseCurrency();
        }
        return $currencyCode;
    }

    /**
     * format price from currency
     *
     * @param $price
     * @return string
     */
    public function format($price)
    {
        return $this->getCurrency()->format($price);
    }

    public function getTitle()
    {
        return $this->__("RFM Matrix") . "(<a href='https://econsultancy.com/blog/64481-finding-your-best-customers-with-the-rfm-matrix' target='_blank'>" . $this->__("Find out more") . "</a>)";
    }
}
