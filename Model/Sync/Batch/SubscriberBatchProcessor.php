<?php

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

use Dotdigitalgroup\Email\Model\Importer;

class SubscriberBatchProcessor extends AbstractBatchProcessor
{
    /**
     * Mark contacts as imported.
     *
     * @param array $contactIds
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function markContactsAsImported($contactIds)
    {
        $this->contactResourceFactory->create()
            ->setSubscribersImportedByIds(
                $contactIds
            );
    }

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
                Importer::IMPORT_TYPE_SUBSCRIBERS,
                '',
                Importer::MODE_BULK,
                $websiteId,
                $filename
            );

        if ($success) {
            $this->logger->info(
                sprintf(
                    '%s subscribers batched for website id %s in file %s',
                    count($batch),
                    $websiteId,
                    $filename
                )
            );
        }
    }
}
