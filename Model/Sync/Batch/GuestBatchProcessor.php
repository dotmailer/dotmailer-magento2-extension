<?php

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

use Dotdigitalgroup\Email\Model\Importer;

class GuestBatchProcessor extends AbstractBatchProcessor
{
    /**
     * Queue batch with importer.
     *
     * @param array $batch
     * @param string|int $websiteId
     * @param string $filename
     *
     * @return void
     */
    protected function addToImportQueue(array $batch, $websiteId, string $filename)
    {
        $success = $this->importerFactory->create()
            ->registerQueue(
                Importer::IMPORT_TYPE_GUEST,
                '',
                Importer::MODE_BULK,
                $websiteId,
                $filename
            );

        if ($success) {
            $this->logger->info(
                sprintf(
                    '%s guests batched for website id %s in file %s',
                    count($batch),
                    $websiteId,
                    $filename
                )
            );
        }
    }
}
