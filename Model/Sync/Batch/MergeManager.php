<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchMergerInterface;

class MergeManager implements BatchMergerInterface
{
    /**
     * @inheritDoc
     */
    public function mergeBatch(array $batch, array $megaBatch)
    {
        foreach ($batch as $id => $data) {
            $megaBatch[$id] = $data;
        }
        return $megaBatch;
    }
}
