<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Handler;

use Dotdigitalgroup\Email\Api\Model\Sync\Importer\InProgressImportResponseHandlerInterface;

class InProgressImportResponseHandlerPool
{
    /**
     * @var array
     */
    private $handlers;

    /**
     * @param array $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * Get handler by code.
     *
     * @param string $handlerCode
     * @return InProgressImportResponseHandlerInterface
     */
    public function get(string $handlerCode): InProgressImportResponseHandlerInterface
    {
        return $this->handlers[$handlerCode];
    }
}
