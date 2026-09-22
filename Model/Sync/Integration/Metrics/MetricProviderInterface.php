<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Integration\Metrics;

/**
 * Contract for integration insight metric providers.
 */
interface MetricProviderInterface
{
    /**
     * Return the metric data for this provider, scoped to a website.
     *
     * @param int $websiteId
     * @return array
     */
    public function getMetricData(int $websiteId): array;
}
