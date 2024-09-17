<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Batch\Sender;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchStrategyInterface;

interface SenderStrategyInterface extends BatchStrategyInterface
{
    /**
     * Sets the batch of data to be processed.
     *
     * @param array $batch
     * @return SenderStrategyInterface
     */
    public function setBatch(array $batch): SenderStrategyInterface;

    /**
     * Sets the website ID associated with the data batch.
     *
     * @param int $websiteId
     * @return SenderStrategyInterface
     */
    public function setWebsiteId(int $websiteId): SenderStrategyInterface;

    /**
     * Processes a batch of records.
     *
     * @return string An import ID, or an empty string.
     */
    public function process(): string;
}
