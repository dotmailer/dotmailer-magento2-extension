<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Sender;

use Dotdigital\Exception\ResponseValidationException;

interface SendDataStrategyInterface
{
    /**
     * Send data to Dotdigital.
     *
     * @param array $batch
     * @param int $websiteId
     *
     * @return string
     * @throws ResponseValidationException
     */
    public function sendDataToDotdigital(array $batch, int $websiteId): string;
}
