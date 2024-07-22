<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Sender;

use Dotdigital\Exception\ResponseValidationException;

class SendDataStrategyHandler
{
    /**
     * @var SendDataStrategyInterface
     */
    private $sendDataStrategy;

    /**
     * @param SendDataStrategyInterface $sendDataStrategy
     */
    public function __construct(
        SendDataStrategyInterface $sendDataStrategy
    ) {
        $this->sendDataStrategy = $sendDataStrategy;
    }

    /**
     * Set strategy.
     *
     * @param SendDataStrategyInterface $sendDataStrategy
     *
     * @return void
     */
    public function setStrategy(SendDataStrategyInterface $sendDataStrategy)
    {
        $this->sendDataStrategy = $sendDataStrategy;
    }

    /**
     * Execute strategy.
     *
     * @param array $batch
     * @param int $websiteId
     *
     * @return string
     * @throws ResponseValidationException
     */
    public function executeStrategy(array $batch, int $websiteId): string
    {
        return $this->sendDataStrategy->sendDataToDotdigital($batch, $websiteId);
    }
}
