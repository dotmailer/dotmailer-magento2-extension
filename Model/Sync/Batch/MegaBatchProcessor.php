<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchProcessorInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Batch\Record\RecordImportedStrategyFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SenderStrategyFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkSaver;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Stdlib\DateTime;

class MegaBatchProcessor implements BatchProcessorInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RecordImportedStrategyFactory
     */
    private $recordImportedStrategyFactory;

    /**
     * @var SenderStrategyFactory
     */
    private $senderStrategyFactory;

    /**
     * @var BulkSaver
     */
    private $importBulkSaver;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * MegaBatchProcessor constructor.
     *
     * @param Logger $logger
     * @param RecordImportedStrategyFactory $recordImportedStrategyFactory
     * @param SenderStrategyFactory $senderStrategyFactory
     * @param BulkSaver $importBulkSaver
     * @param DateTime $dateTime
     */
    public function __construct(
        Logger $logger,
        RecordImportedStrategyFactory $recordImportedStrategyFactory,
        SenderStrategyFactory $senderStrategyFactory,
        BulkSaver $importBulkSaver,
        DateTime $dateTime
    ) {
        $this->logger = $logger;
        $this->recordImportedStrategyFactory = $recordImportedStrategyFactory;
        $this->senderStrategyFactory = $senderStrategyFactory;
        $this->importBulkSaver = $importBulkSaver;
        $this->dateTime = $dateTime;
    }

    /**
     * Process a completed batch.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $importType
     * @param string $bulkImportMode
     * @throws AlreadyExistsException
     */
    public function process(
        array $batch,
        int $websiteId,
        string $importType,
        string $bulkImportMode = ImporterModel::MODE_BULK_JSON
    ) {
        if (empty($batch)) {
            return;
        }

        try {
            $importId = $this->sendBatch($batch, $websiteId, $importType);
            if ($importId) {
                $this->importBulkSaver->addInProgressBatchToImportTable(
                    $batch,
                    $websiteId,
                    $importId,
                    $importType,
                    $this->dateTime->formatDate(true),
                    $bulkImportMode
                );
            }

            $this->logger->info(
                sprintf(
                    '%s %s records batched for website id %s',
                    count($batch),
                    strtolower($importType),
                    $websiteId
                )
            );

        } catch (ResponseValidationException $e) {
            $this->logger->error((string) $e);
            $this->importBulkSaver->addFailedBatchToImportTable(
                $batch,
                $websiteId,
                $e->getMessage(),
                $importType,
                $bulkImportMode
            );

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'Unexpected error sending batch: import type (%s) / website id (%s) / %s',
                    $importType,
                    $websiteId,
                    $e->getMessage()
                )
            );
            $this->importBulkSaver->addFailedBatchToImportTable(
                $batch,
                $websiteId,
                $e->getMessage(),
                $importType,
                $bulkImportMode
            );

        } finally {
            $this->markAsImported($batch, $importType);
        }
    }

    /**
     * Sends a batch of data for processing based on the import type.
     *
     * This method is responsible for sending a batch of data to the appropriate sender strategy,
     * determined by the import type. It utilizes the SenderStrategyFactory to create an instance
     * of the sender strategy, sets the batch and website ID on the strategy, and then calls the
     * process method on the strategy to handle the data. The process method is expected to return
     * a string that represents the import ID, which is then returned by this method.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $importType
     *
     * @return string The import ID returned by the sender strategy's process method.
     */
    private function sendBatch(array $batch, int $websiteId, string $importType): string
    {
        return $this->senderStrategyFactory->create($importType)
            ->setBatch($batch)
            ->setWebsiteId($websiteId)
            ->process();
    }

    /**
     * Marks a batch of records as imported based on the import type.
     *
     * This method utilizes the RecordImportedStrategyFactory to create a strategy instance
     * specific to the provided import type. It then sets the record IDs that have been imported
     * and processes them according to the strategy's implementation. This could involve updating
     * database records, sending notifications, or other post-import actions.
     *
     * @param array $batch
     * @param string $importType The type of import (e.g., customer, guest, subscribers)
     */
    private function markAsImported(array $batch, string $importType): void
    {
        $this->recordImportedStrategyFactory
            ->create($importType)
            ->setRecords($batch)
            ->process();
    }
}
