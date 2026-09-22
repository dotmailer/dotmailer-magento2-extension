<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Importer\Context\InProgressImportContext;

/**
 * Contract for handlers that poll Dotdigital for the status of in-progress imports.
 *
 * Implementations are responsible for checking the status of every importer row
 * in the supplied collection, updating each row accordingly, and reporting how
 * many rows are still importing.
 */
interface InProgressImportResponseHandlerInterface
{
    /**
     * Check and process in-progress importer rows.
     *
     * @param InProgressImportContext $context
     * @param ImporterCollection $items
     * @return int Number of items still in progress.
     */
    public function process(InProgressImportContext $context, ImporterCollection $items): int;
}
