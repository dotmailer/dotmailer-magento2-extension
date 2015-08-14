<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Customer_Tab_Stats extends Mage_Adminhtml_Block_Template
{
    private $_stat = array();

    public function _construct()
    {
        $this->setTemplate('connector/customer/stats.phtml');
    }

    private function _getCampaignStatsForCustomer()
    {
        $id = Mage::app()->getRequest()->getParam('id');
        $customer = Mage::getModel('customer/customer')->load($id);
        $email = $customer->getEmail();
        $website = $customer->getStore()->getWebsite();

        $client = Mage::helper('ddg')->getWebsiteApiClient($website);
        $contact = $client->postContacts($email);
        if(!isset($contact->message)){
            $locale = Mage::app()->getLocale()->getLocale();
            $date = Zend_Date::now($locale)->subDay(30);
            $response = $client->getCampaignsWithActivitySinceDate($date->toString(Zend_Date::ISO_8601));
            if(!isset($response->message) && is_array($response)){
                foreach($response as $one){
                    $result = $client->getCampaignActivityByContactId($one->id, $contact->id);
                    if(!empty($result) && !isset($result->message) && !is_null($result)){
                        $this->_stat[$one->name] = $result;
                    }
                }
            }
        }
    }

    public function getStats()
    {
        $this->_getCampaignStatsForCustomer();
        return $this->_stat;
    }
}