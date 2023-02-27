<?php

namespace Dotdigitalgroup\Email\Model\Queue\Sync;

use Dotdigitalgroup\Email\Model\Queue\ConsumerInterface;
use Dotdigitalgroup\Email\Model\Sync\Integration\IntegrationInsights;

class IntegrationInsightsConsumer implements ConsumerInterface
{
    /**
     * @var IntegrationInsights $integrationInsights
     */
    private $integrationInsights;

    /**
     * IntegrationInsightsConsumer constructor.
     *
     * @param IntegrationInsights $integrationInsights
     */
    public function __construct(
        IntegrationInsights $integrationInsights
    ) {
        $this->integrationInsights = $integrationInsights;
    }

    /**
     * @inheritDoc
     */
    public function process(string $message): void
    {
        $this->integrationInsights->sync();
    }
}
