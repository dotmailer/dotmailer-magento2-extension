<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler;

use Dotdigital\V3\Models\Contact\Import as SdkImport;
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
     * @param SdkImport $response
     * @return void
     */
    public function process(SdkImport $response)
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
