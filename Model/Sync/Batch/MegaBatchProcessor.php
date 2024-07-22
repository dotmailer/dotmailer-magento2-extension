<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\Sync\Batch\Record\ContactImportedStrategy;
use Dotdigitalgroup\Email\Model\Sync\Batch\Record\RecordImportedStateHandler;
use Dotdigitalgroup\Email\Model\Sync\Batch\Record\SubscriberImportedStrategy;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SendDataStrategyHandler;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SendContactDataStrategy;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkSaver;
use Http\Client\Exception;
use InvalidArgumentException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;

class MegaBatchProcessor implements BatchProcessorInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactImportedStrategy
     */
    private $contactImportedStrategy;

    /**
     * @var RecordImportedStateHandler
     */
    private $recordImportedStateHandler;

    /**
     * @var SubscriberImportedStrategy
     */
    private $subscriberImportedStrategy;

    /**
     * @var SendDataStrategyHandler
     */
    private $sendDataStrategyHandler;

    /**
     * @var SendDataStrategyHandler
     */
    private $sendContactDataStrategy;

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
     * @param ContactImportedStrategy $contactImportedStrategy
     * @param RecordImportedStateHandler $recordImportedStateHandler
     * @param SubscriberImportedStrategy $subscriberImportedStrategy
     * @param SendDataStrategyHandler $sendDataStrategyHandler
     * @param SendContactDataStrategy $sendContactDataStrategy
     * @param BulkSaver $importBulkSaver
     * @param DateTime $dateTime
     */
    public function __construct(
        Logger $logger,
        ContactImportedStrategy $contactImportedStrategy,
        RecordImportedStateHandler $recordImportedStateHandler,
        SubscriberImportedStrategy $subscriberImportedStrategy,
        SendDataStrategyHandler $sendDataStrategyHandler,
        SendContactDataStrategy $sendContactDataStrategy,
        BulkSaver $importBulkSaver,
        DateTime $dateTime
    ) {
        $this->logger = $logger;
        $this->contactImportedStrategy = $contactImportedStrategy;
        $this->recordImportedStateHandler = $recordImportedStateHandler;
        $this->subscriberImportedStrategy = $subscriberImportedStrategy;
        $this->sendDataStrategyHandler = $sendDataStrategyHandler;
        $this->sendContactDataStrategy = $sendContactDataStrategy;
        $this->importBulkSaver = $importBulkSaver;
        $this->dateTime = $dateTime;
    }

    /**
     * Process a completed batch.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $importType
     *
     * @throws LocalizedException|Exception
     */
    public function process(array $batch, int $websiteId, string $importType)
    {
        if (empty($batch)) {
            return;
        }

        try {
            $importId = $this->pushBatch($batch, $websiteId, $importType);

            if ($importId) {
                $this->importBulkSaver->addInProgressBatchToImportTable(
                    $batch,
                    $websiteId,
                    $importId,
                    $importType,
                    $this->dateTime->formatDate(true)
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
                $importType
            );
        } catch (AlreadyExistsException | InvalidArgumentException $e) {
            $this->logger->error(
                sprintf(
                    "Data save error: import type (%s) / website id (%s) / %s",
                    $importType,
                    $websiteId,
                    $e->getMessage()
                )
            );
        } finally {
            $batchEntityIdentifiers = array_keys($batch);
            $this->markAsImported($batchEntityIdentifiers, $importType);
        }
    }

    /**
     * Send batch to Dotdigital.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $importType
     *
     * @return string
     * @throws ResponseValidationException
     */
    private function pushBatch(array $batch, int $websiteId, string $importType): string
    {
        if ($importType === Importer::IMPORT_TYPE_CUSTOMER ||
            $importType === Importer::IMPORT_TYPE_GUEST ||
            $importType === Importer::IMPORT_TYPE_SUBSCRIBERS
        ) {
            $this->sendDataStrategyHandler->setStrategy($this->sendContactDataStrategy);
        }

        return $this->sendDataStrategyHandler->executeStrategy($batch, $websiteId);
    }

    /**
     * Mark contacts as imported.
     *
     * @param array $ids
     * @param string $importType
     *
     * @return void
     */
    private function markAsImported($ids, $importType)
    {
        if ($importType === Importer::IMPORT_TYPE_CUSTOMER || $importType === Importer::IMPORT_TYPE_GUEST) {
            $this->recordImportedStateHandler->setStrategy($this->contactImportedStrategy);
        } elseif ($importType === Importer::IMPORT_TYPE_SUBSCRIBERS) {
            $this->recordImportedStateHandler->setStrategy($this->subscriberImportedStrategy);
        }

        $this->recordImportedStateHandler->executeStrategy($ids);
    }
}
