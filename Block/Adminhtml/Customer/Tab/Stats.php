<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Customer\Tab;

class Stats extends \Magento\Framework\View\Element\Template
{

    protected $_stat = array();

    protected $_helper;
    protected $_customer;

    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Model\Customer $customer
    ) {
        $data                 = [];
        $this->_helper        = $helper;
        $this->_customer = $customer;
        parent::__construct($context, $data);
    }


    public function _construct()
    {
        $this->setTemplate('connector/customer/stats.phtml');
    }

    protected function _getCampaignStatsForCustomer()
    {
        $id      = $this->_request->getParam('id');
        $customer
            = $this->_customer->load($id);
        $email   = $customer->getEmail();
        $website = $customer->getStore()->getWebsite();

        $client  = $this->_helper->getWebsiteApiClient($website);
        $contact = $client->postContacts($email);
        if ( ! isset($contact->message)) {

            $date = \Zend_Date::now()->subDay(30);
            $response
                  = $client->getCampaignsWithActivitySinceDate($date->toString(\Zend_Date::ISO_8601));
            if ( ! isset($response->message) && is_array($response)) {
                foreach ($response as $one) {
                    $result = $client->getCampaignActivityByContactId($one->id,
                        $contact->id);
                    if ( ! empty($result) && ! isset($result->message)
                        && ! is_null($result)
                    ) {
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