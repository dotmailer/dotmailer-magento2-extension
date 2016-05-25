<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Carts;

class Campaigns implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * Campaigns constructor.
     *
     * @param \Magento\Framework\Registry        $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_registry = $registry;
        $this->_helper = $data;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $fields = [];
        $fields[] = ['value' => '0', 'label' => '-- Please Select --'];

        $apiEnabled = $this->_helper->isEnabled($this->_helper->getWebsite());

        if ($apiEnabled) {
            $savedCampaigns = $this->_registry->registry('campaigns');

            if (is_array($savedCampaigns)) {
                $campaigns = $savedCampaigns;
            } else {
                //grab the datafields request and save to register
                $client = $this->_helper->getWebsiteApiClient();
                $campaigns = $client->getCampaigns();
                $this->_registry->register('campaigns', $campaigns);
            }

            //set the api error message for the first option
            if (isset($campaigns->message)) {
                //message
                $fields[] = ['value' => 0, 'label' => $campaigns->message];
            } elseif (!empty($campaigns)) {
                //loop for all campaing options
                foreach ($campaigns as $campaign) {
                    if (isset($campaign->name)) {
                        $fields[] = [
                            'value' => $campaign->id,
                            'label' => addslashes($campaign->name),
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
