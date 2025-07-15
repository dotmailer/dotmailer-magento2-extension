<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchMergerInterface;

class CatalogMergeManager implements BatchMergerInterface
{

    /**
     * @inheritDoc
     */
    public function mergeBatch(array $batch, array $megaBatch)
    {
        foreach ($batch as $catalogName => $set) {
            if (array_key_exists($catalogName, $megaBatch)) {
                if (isset($set['products'])) {
                    $megaBatch[$catalogName]['products'] += $set['products'];
                }
            } else {
                $megaBatch += [$catalogName => $set];
            }
        }

        return $megaBatch;
    }
}
