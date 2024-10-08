<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Batch\Record;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchStrategyInterface;

interface RecordImportedStrategyInterface extends BatchStrategyInterface
{
    /**
     * Sets the data to be processed by the strategy.
     *
     * @param array $records
     * @return RecordImportedStrategyInterface
     */
    public function setRecords(array $records): RecordImportedStrategyInterface;

    /**
     * Processes a batch of records.
     *
     * @return void
     */
    public function process(): void;
}
