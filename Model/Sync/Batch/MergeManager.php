<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

class MergeManager
{
    /**
     * Creates the mega batch.
     *
     * For any type of contact sync, batches will only ever be for one website.
     *
     * @param array $batch
     * @param array $megaBatch
     *
     * @return array
     */
    public function mergeBatch(array $batch, array $megaBatch)
    {
        foreach ($batch as $id => $data) {
            $megaBatch[$id] = $data;
        }
        return $megaBatch;
    }
}
