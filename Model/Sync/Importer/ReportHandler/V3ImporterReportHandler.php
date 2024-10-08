<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler;

use Dotdigital\V3\Models\Import\ImportInterface as V3ImportInterface;
use Dotdigitalgroup\Email\Logger\Logger;

class V3ImporterReportHandler
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Process.
     *
     * @param V3ImportInterface $response
     * @return void
     */
    public function process(V3ImportInterface $response)
    {
        if ($response->getSummary()) {
            $this->logger->info(
                sprintf(
                    'Import id %s finished, summary: %s',
                    $response->getImportId(),
                    json_encode($response->getSummary())
                )
            );
        }

        if ($response->getFailures()) {
            foreach ($response->getFailures() as $failure) {
                $this->logger->debug(
                    sprintf(
                        'Import id %s failure: %s',
                        $response->getImportId(),
                        json_encode($failure)
                    )
                );
            }
        }
    }
}
