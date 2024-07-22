<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

interface BatchProcessorInterface
{
    /**
     * Process a batch.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $importType
     *
     * @return void
     */
    public function process(array $batch, int $websiteId, string $importType);
}
