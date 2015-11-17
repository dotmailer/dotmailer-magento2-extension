<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Customer\Tab;

class Stats extends \Magento\Framework\View\Element\Template
{
    private $_stat = array();

	protected $_helper;
	protected $_objectManager;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		\Magento\Backend\Block\Template\Context $context)
	{
		$data = [];
		$this->_helper = $data;
		$this->_objectManager = $objectManagerInterface;
		parent::__construct($context, $data);
	}


    public function _construct()
    {
        $this->setTemplate('connector/customer/stats.phtml');
    }

    private function _getCampaignStatsForCustomer()
    {
	    $id = $this->_request->getParam('id');
        $customer = $this->_objectManager->create('Magento\Customer\Model\Customer')
	        ->load($id);
        $email = $customer->getEmail();
        $website = $customer->getStore()->getWebsite();

        $client = $this->_helper->getWebsiteApiClient($website);
        $contact = $client->postContacts($email);
        if(!isset($contact->message)){

            $date = \Zend_Date::now()->subDay(30);
            $response = $client->getCampaignsWithActivitySinceDate($date->toString(\Zend_Date::ISO_8601));
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