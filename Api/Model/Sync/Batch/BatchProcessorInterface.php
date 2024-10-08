<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Batch;

interface BatchProcessorInterface
{
    /**
     * Processes a batch of data for a specific import type and website.
     *
     * @param array $batch An array of data to be processed.
     * @param int $websiteId The ID of the website the batch is associated with.
     * @param string $importType The type of import, which determines the processing logic to be applied.
     * @param string $bulkImportMode The type of import mode.
     */
    public function process(array $batch, int $websiteId, string $importType, string $bulkImportMode);
}
