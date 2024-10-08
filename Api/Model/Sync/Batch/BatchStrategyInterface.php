<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Batch;

interface BatchStrategyInterface
{
    /**
     * Processes the data set by setData method.
     *
     * This method is the core of the strategy, where the actual processing of the data happens.
     * It should contain the logic specific to the strategy's purpose, such as sending emails,
     * importing records, or any other task that the strategy is designed to perform.
     *
     * @return mixed
     */
    public function process();
}
