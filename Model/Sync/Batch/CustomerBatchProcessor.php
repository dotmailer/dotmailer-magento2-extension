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
class CustomerBatchProcessor extends AbstractBatchProcessor
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
     * CustomerBatchProcessor constructor.
     *
     * @param File $file
     * @param ImporterFactory $importerFactory
     * @param Logger $logger
     * @param ContactResourceFactory $contactResourceFactory
     * @param DriverInterface $driver
     */
    public function __construct(
        File $file,
        ImporterFactory $importerFactory,
        Logger $logger,
        ContactResourceFactory $contactResourceFactory,
        DriverInterface $driver
    ) {
        $this->importerFactory = $importerFactory;
        $this->logger = $logger;
        parent::__construct($file, $contactResourceFactory, $driver);
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
                Importer::IMPORT_TYPE_CUSTOMER,
                '',
                Importer::MODE_BULK,
                $websiteId,
                $filename
            );

        if ($success) {
            $this->logger->info(
                sprintf(
                    '%s customers batched for website id %s in file %s',
                    count($batch),
                    $websiteId,
                    $filename
                )
            );
        }
    }
}
