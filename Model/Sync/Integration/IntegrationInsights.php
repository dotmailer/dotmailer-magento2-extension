<?php

namespace Dotdigitalgroup\Email\Model\Sync\Integration;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Sync\SyncInterface;

class IntegrationInsights implements SyncInterface
{
    public const TOPIC_SYNC_INTEGRATION = 'ddg.sync.integration';

    /**
     * @var IntegrationInsightData
     */
    private $insightData;

    /**
     * @var Data
     */
    private $helper;

    /**
     * IntegrationInsights constructor.
     *
     * @param IntegrationInsightData $insightData
     * @param Data $helper
     */
    public function __construct(IntegrationInsightData $insightData, Data $helper)
    {
        $this->insightData = $insightData;
        $this->helper = $helper;
    }

    /**
     * Sync.
     *
     * @param \DateTime|null $from
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return bool
     */
    public function sync(?\DateTime $from = null)
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
