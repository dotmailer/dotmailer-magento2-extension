<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Batch;

interface BatchStrategyFactoryInterface
{
    /**
     * Creates a batch strategy instance based on the specified import type.
     *
     * @param string $importType The type of import for which the strategy is created.
     * @return BatchStrategyInterface
     */
    public function create(string $importType): BatchStrategyInterface;
}
