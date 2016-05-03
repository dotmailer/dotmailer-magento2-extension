<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Carts;

class Campaigns implements \Magento\Framework\Option\ArrayInterface
{

    protected $_helper;
    protected $_registry;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_registry = $registry;
        $this->_helper   = $data;
    }


    public function toOptionArray()
    {
        $fields   = array();
        $fields[] = array('value' => '0', 'label' => '-- Please Select --');

        $apiEnabled = $this->_helper->isEnabled($this->_helper->getWebsite());

        if ($apiEnabled) {
            $savedCampaigns = $this->_registry->registry('campaigns');

            if ($savedCampaigns) {
                $campaigns = $savedCampaigns;
            } else {
                //grab the datafields request and save to register
                $client = $this->_helper->getWebsiteApiClient();

                $campaigns = $client->GetCampaigns();
                $this->_registry->register('campaigns', $campaigns);
            }

            //set the api error message for the first option
            if (isset($campaigns->message)) {
                //message
                $fields[] = array('value' => 0, 'label' => $campaigns->message);
            } else {
                //loop for all campaing option
                foreach ($campaigns as $campaign) {
                    $fields[] = array(
                        'value' => (string)$campaign->id,
                        'label' => addslashes((string)$campaign->name)
                    );
                }
            }
        }


        return $fields;
    }

}