<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob\ImporterLink as CouponJobImporterLinkResource;
use Magento\Framework\Exception\AlreadyExistsException;

class BulkSaver
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var CouponJobImporterLinkResource
     */
    private $couponJobImporterLinkResource;

    /**
     * Bulk Saver constructor.
     *
     * @param Logger $logger
     * @param ImporterFactory $importerFactory
     * @param CouponJobImporterLinkResource $couponJobImporterLinkResource
     */
    public function __construct(
        Logger $logger,
        ImporterFactory $importerFactory,
        CouponJobImporterLinkResource $couponJobImporterLinkResource
    ) {
        $this->logger = $logger;
        $this->importerFactory = $importerFactory;
        $this->couponJobImporterLinkResource = $couponJobImporterLinkResource;
    }

    /**
     * Add batch to importer as 'Importing'.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $importId
     * @param string $importType
     * @param string $importStarted
     * @param string $mode
     *
     * @return void
     */
    public function addInProgressBatchToImportTable(
        array $batch,
        int $websiteId,
        string $importId,
        string $importType,
        string $importStarted,
        string $mode
    ) {
        try {
            $importer = $this->importerFactory->create();
            $importer->addToImporterQueue(
                $importType,
                $batch,
                $mode,
                $websiteId,
                0,
                Importer::IMPORTING,
                $importId,
                '',
                $importStarted
            );
            $this->linkCouponJobImporter($importType, $batch, $importer);
        } catch (AlreadyExistsException $e) {
            $this->logger->error(
                sprintf(
                    "Data save error (in-progress batch): import type (%s) / website id (%s) / %s",
                    $importType,
                    $websiteId,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Add batch to importer as 'Failed'.
     *
     * @param array $batch
     * @param int $websiteId
     * @param string $message
     * @param string $importType
     * @param string $mode
     *
     * @return void
     */
    public function addFailedBatchToImportTable(
        array $batch,
        int $websiteId,
        string $message,
        string $importType,
        string $mode
    ) {
        try {
            $importer = $this->importerFactory->create();
            $importer->addToImporterQueue(
                $importType,
                $batch,
                $mode,
                $websiteId,
                0,
                Importer::FAILED,
                '',
                $message
            );
            $this->linkCouponJobImporter($importType, $batch, $importer);
        } catch (AlreadyExistsException $e) {
            $this->logger->error(
                sprintf(
                    "Data save error (failed batch): import type (%s) / website id (%s) / %s",
                    $importType,
                    $websiteId,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Record a coupon-job-to-importer link for CouponJob batches.
     *
     * CouponJob batches are wrapped as {"coupon_job_id":<id>,"records":{...}}.
     * Storing the importer row id against the coupon job id lets the report
     * builder fetch a job's batches via an indexed join instead of a LIKE scan
     * over the serialized import_data column.
     *
     * @param string $importType
     * @param array $batch
     * @param Importer $importer
     * @return void
     */
    private function linkCouponJobImporter(string $importType, array $batch, Importer $importer): void
    {
        if ($importType !== Importer::IMPORT_TYPE_COUPON_JOB) {
            return;
        }

        $couponJobId = (int) ($batch['coupon_job_id'] ?? 0);
        $importerId = (int) $importer->getId();

        if ($couponJobId <= 0 || $importerId <= 0) {
            return;
        }

        try {
            $this->couponJobImporterLinkResource->linkImporter($couponJobId, $importerId);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'BulkSaver: could not link coupon job %d to importer %d: %s',
                    $couponJobId,
                    $importerId,
                    $e->getMessage()
                )
            );
        }
    }
}
