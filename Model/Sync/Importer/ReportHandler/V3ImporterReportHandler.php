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
        $this->logger->debug(
            sprintf(
                'Import id %s summary: %s',
                $response->getImportId(),
                json_encode($response->getSummary())
            )
        );
    }
}
