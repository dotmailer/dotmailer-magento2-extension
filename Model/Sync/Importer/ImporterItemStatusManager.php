<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;

/**
 * Owns importer row state transitions for in-progress import handlers.
 *
 * Composed into the in-progress import response handlers so that the importer
 * resource, the terminal status list and the row transitions live in one place
 * instead of being inherited state.
 */
class ImporterItemStatusManager
{
    /**
     * Import statuses reported by Dotdigital that mean the import will not complete.
     */
    public const FAILED_IMPORT_STATUSES = [
        'RejectedByWatchdog',
        'InvalidFileFormat',
        'Unknown',
        'Failed',
        'ExceedsAllowedContactLimit',
        'NotAvailableInThisVersion',
    ];

    /**
     * @var ImporterResource
     */
    private $importerResource;

    /**
     * @param ImporterResource $importerResource
     */
    public function __construct(
        ImporterResource $importerResource
    ) {
        $this->importerResource = $importerResource;
    }

    /**
     * Check if a status reported by Dotdigital is a terminal failure.
     *
     * @param string|null $status
     * @return bool
     */
    public function isFailedStatus($status): bool
    {
        return in_array($status, self::FAILED_IMPORT_STATUSES, true);
    }

    /**
     * Mark an importer row as imported.
     *
     * @param ImporterModel $item
     * @return ImporterModel
     */
    public function markImported(ImporterModel $item): ImporterModel
    {
        $item->setImportStatus(ImporterModel::IMPORTED)
            ->setImportFinished(gmdate('Y-m-d H:i:s'))
            ->setMessage('');

        return $item;
    }

    /**
     * Mark an importer row as failed.
     *
     * @param ImporterModel $item
     * @param string $message
     * @return ImporterModel
     */
    public function markFailed(ImporterModel $item, string $message): ImporterModel
    {
        $item->setImportStatus(ImporterModel::FAILED)
            ->setMessage($message);

        return $item;
    }

    /**
     * Save an importer row.
     *
     * @param ImporterModel $item
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(ImporterModel $item): void
    {
        $this->importerResource->save($item);
    }
}
