<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\IntegrationInsightData;

class IntegrationInsights implements SyncInterface
{
    /**
     * @var IntegrationInsightData
     */
    private $insightData;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param IntegrationInsightData $insightData
     * @param Data $helper
     */
    public function __construct(IntegrationInsightData $insightData, Data $helper)
    {
        $this->insightData = $insightData;
        $this->helper = $helper;
    }

    /**
     * @param \DateTime|null $from
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return bool
     */
    public function sync(\DateTime $from = null)
    {
        if (!(bool) $this->helper->getConfigValue(Config::XML_PATH_CONNECTOR_INTEGRATION_INSIGHTS_ENABLED)) {
            return false;
        }

        foreach ($this->insightData->getIntegrationInsightData() as $websiteId => $integration) {
            $result = $this->helper->getWebsiteApiClient($websiteId)
                ->postIntegrationInsightData($integration);

            $this->helper->log('Integration insight data sent', [
                'integration' => $integration['recordId'],
                'success' => $result,
            ]);
        }

        return true;
    }
}
