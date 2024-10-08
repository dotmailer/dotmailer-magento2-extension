<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use InvalidArgumentException;
use Magento\Framework\Exception\AlreadyExistsException;

class BulkSaver
{
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
     * @throws AlreadyExistsException
     */
    public function addInProgressBatchToImportTable(
        array $batch,
        int $websiteId,
        string $importId,
        string $importType,
        string $importStarted,
        string $mode
    ) {
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
     * @throws AlreadyExistsException
     */
    public function addFailedBatchToImportTable(
        array $batch,
        int $websiteId,
        string $message,
        string $importType,
        string $mode
    ) {
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
    }
}
