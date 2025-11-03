<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Magento\Framework\Exception\AlreadyExistsException;

class BulkSaver
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * Bulk Saver constructor.
     *
     * @param ImporterFactory $importerFactory
     */
    public function __construct(
        ImporterFactory $importerFactory
    ) {
        $this->importerFactory = $importerFactory;
    }

    /**
     * Add batch to importer as 'Importing'.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $importId
     * @param string $importType
     * @param string $importStarted
     * @param string $mode
     *
     * @return void
     */
    public function addInProgressBatchToImportTable(
        array $batch,
        int $websiteId,
        string $importId,
        string $importType,
        string $importStarted,
        string $mode
    ) {
        try {
            $this->importerFactory->create()
                ->addToImporterQueue(
                    $importType,
                    $batch,
                    $mode,
                    $websiteId,
                    0,
                    Importer::IMPORTING,
                    $importId,
                    '',
                    $importStarted
                );
        } catch (AlreadyExistsException $e) {
            $this->logger->error(
                sprintf(
                    "Data save error (in-progress batch): import type (%s) / website id (%s) / %s",
                    $importType,
                    $websiteId,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Add batch to importer as 'Failed'.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $message
     * @param string $importType
     * @param string $mode
     *
     * @return void
     */
    public function addFailedBatchToImportTable(
        array $batch,
        int $websiteId,
        string $message,
        string $importType,
        string $mode
    ) {
        try {
            $this->importerFactory->create()
                ->addToImporterQueue(
                    $importType,
                    $batch,
                    $mode,
                    $websiteId,
                    0,
                    Importer::FAILED,
                    '',
                    $message
                );
        } catch (AlreadyExistsException $e) {
            $this->logger->error(
                sprintf(
                    "Data save error (failed batch): import type (%s) / website id (%s) / %s",
                    $importType,
                    $websiteId,
                    $e->getMessage()
                )
            );
        }
    }
}
