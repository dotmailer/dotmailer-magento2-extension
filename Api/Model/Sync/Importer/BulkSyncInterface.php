<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;

interface BulkSyncInterface
{
    /**
     * Process a single importer item.
     *
     * @param ImporterModel $item
     * @return mixed
     */
    public function process(ImporterModel $item);
}
