<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

class RecordImportedStateHandler
{
    /**
     * @var RecordImportedStrategyInterface
     */
    private $recordImportedStrategy;

    /**
     * @param RecordImportedStrategyInterface $recordImportedStrategy
     */
    public function __construct(
        RecordImportedStrategyInterface $recordImportedStrategy
    ) {
        $this->recordImportedStrategy = $recordImportedStrategy;
    }

    /**
     * Set strategy.
     *
     * @param RecordImportedStrategyInterface $recordImportedStrategy
     *
     * @return void
     */
    public function setStrategy(RecordImportedStrategyInterface $recordImportedStrategy)
    {
        $this->recordImportedStrategy = $recordImportedStrategy;
    }

    /**
     * Execute strategy.
     *
     * @param array $ids
     *
     * @return void
     */
    public function executeStrategy(array $ids): void
    {
        $this->recordImportedStrategy->markAsImported($ids);
    }
}
