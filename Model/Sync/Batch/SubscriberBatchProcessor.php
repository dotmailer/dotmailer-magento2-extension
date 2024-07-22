<?php

namespace Dotdigitalgroup\Email\Model\Sync\Batch;

use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory as ContactResourceFactory;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * @deprecated We consolidated the batch processors into one class. This class will be removed in a future release.
 * @see \Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor
 */
class SubscriberBatchProcessor extends AbstractBatchProcessor
{
    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * SubscriberBatchProcessor constructor.
     *
     * @param File $file
     * @param ContactResourceFactory $contactResourceFactory
     * @param DriverInterface $driver
     * @param ImporterFactory $importerFactory
     * @param Logger $logger
     */
    public function __construct(
        File $file,
        ContactResourceFactory $contactResourceFactory,
        DriverInterface $driver,
        ImporterFactory $importerFactory,
        Logger $logger
    ) {
        $this->importerFactory = $importerFactory;
        $this->logger = $logger;
        parent::__construct($file, $contactResourceFactory, $driver);
    }

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
