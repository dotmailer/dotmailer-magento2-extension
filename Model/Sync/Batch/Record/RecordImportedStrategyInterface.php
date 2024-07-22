<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

interface RecordImportedStrategyInterface
{
    /**
     * Mark as imported.
     *
     * @param array $ids
     *
     * @return void
     */
    public function markAsImported(array $ids): void;
}
