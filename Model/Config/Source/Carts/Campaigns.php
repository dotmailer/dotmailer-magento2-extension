<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Carts;

class Campaigns implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * Campaigns constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->escaper = $escaper;
        $this->registry = $registry;
        $this->helper   = $data;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $fields = [];
        $fields[] = ['value' => '0', 'label' => '-- Please Select --'];

        $apiEnabled = $this->helper->isEnabled($this->helper->getWebsite());

        if ($apiEnabled) {
            $savedCampaigns = $this->registry->registry('campaigns');

            if (is_array($savedCampaigns)) {
                $campaigns = $savedCampaigns;
            } else {
                //grab the datafields request and save to register
                $client = $this->helper->getWebsiteApiClient($this->helper->getWebsite());
                $campaigns = $client->getCampaigns();
                $this->registry->unregister('campaigns'); // additional measure
                $this->registry->register('campaigns', $campaigns);
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
                            'label' => $campaign->name,
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
