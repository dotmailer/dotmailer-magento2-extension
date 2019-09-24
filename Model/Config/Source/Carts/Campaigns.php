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
        $fields = [[
            'value' => '0',
            'label' => '-- Please Select --',
        ]];

        $apiEnabled = $this->helper->isEnabled($this->helper->getWebsiteForSelectedScopeInAdmin());

        if ($apiEnabled) {
            $savedCampaigns = $this->registry->registry('campaigns');

            if (is_array($savedCampaigns)) {
                $campaigns = $savedCampaigns;
            } else {
                $campaigns = $this->fetchCampaigns();
            }

            //set the api error message for the first option
            if (isset($campaigns['message'])) {
                //message
                $fields[] = ['value' => 0, 'label' => $campaigns['message']];
            } elseif (!empty($campaigns)) {
                //loop for all campaign options
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

    /**
     * @return array
     * @throws \Exception
     */
    private function fetchCampaigns()
    {
        //grab the datafields request and save to register
        $client = $this->helper->getWebsiteApiClient($this->helper->getWebsiteForSelectedScopeInAdmin());
        $campaigns = [];

        do {
            // due to the API limitation of 1000 campaign responses, loop while the campaigns returned === 1000,
            // skipping by the count of the total received so far
            if (!is_array($campaignResponse = $client->getCampaigns(count($campaigns)))) {
                return (array) $campaignResponse;
            }
            $campaigns = array_merge($campaigns, $campaignResponse);
        } while (count($campaignResponse) === 1000);

        $this->registry->unregister('campaigns');
        $this->registry->register('campaigns', $campaigns);

        return $campaigns;
    }
}
