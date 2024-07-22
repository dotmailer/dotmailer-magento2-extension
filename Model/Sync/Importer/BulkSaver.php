<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use InvalidArgumentException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;

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
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws CouldNotSaveException
     */
    public function addInProgressBatchToImportTable(
        array $batch,
        int $websiteId,
        string $importId,
        string $importType,
        string $importStarted
    ) {
        $this->importerFactory->create()
            ->addToImporterQueue(
                $importType,
                $batch,
                Importer::MODE_BULK,
                $websiteId,
                0,
                Importer::IMPORTING,
                $importId,
                '',
                $importStarted
            );
    }

    /**
     * Add batch to importer as 'Importing'.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $message
     * @param string $importType
     *
     * @return void
     * @throws CouldNotSaveException|AlreadyExistsException|InvalidArgumentException
     */
    public function addFailedBatchToImportTable(array $batch, int $websiteId, string $message, string $importType)
    {
        $this->importerFactory->create()
            ->addToImporterQueue(
                $importType,
                $batch,
                Importer::MODE_BULK,
                $websiteId,
                0,
                Importer::FAILED,
                '',
                $message
            );
    }
}
