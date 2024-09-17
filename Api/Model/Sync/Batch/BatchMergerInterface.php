<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Batch;

interface BatchMergerInterface
{
    /**
     * Merge two batches of data.
     *
     * @param array $batch
     * @param array $megaBatch
     * @return array
     */
    public function mergeBatch(array $batch, array $megaBatch);
}
