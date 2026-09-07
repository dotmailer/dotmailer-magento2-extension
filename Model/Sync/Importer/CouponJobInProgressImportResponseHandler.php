<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigital\V3\Models\Import\ImportInterface as V3ImportInterface;
use Dotdigitalgroup\Email\Api\Model\Sync\Importer\InProgressImportResponseHandlerInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\ReportBuilderInterface as CouponJobReportBuilderInterface;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Importer\Context\InProgressImportContext;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Handles in-progress import polling exclusively for CouponJob batches.
 *
 * Kept separate from V3InProgressImportResponseHandler because:
 *  - CouponJob contacts do not exist in Magento, so storeContactIds must not be called.
 *  - Failure aggregation is reported back to the CouponJob record via ReportBuilder.
 *  - The logic is unrelated to the standard contact/consent/subscriber import flow.
 *
 * Polling is deliberately status-agnostic: a row only reaches this handler once
 * Dotdigital has accepted its contacts and returned an import id, so it is polled to
 * completion even if the coupon job has since been cancelled. Abandoning it here
 * would silently discard work that the platform actually performed and leave the
 * merchant with batch and record counts lower than reality. Cancellation is enforced
 * upstream instead — the consumers stop producing batches and BulkJson refuses to
 * send those that were never dispatched.
 */
class CouponJobInProgressImportResponseHandler implements InProgressImportResponseHandlerInterface
{
    /**
     * Prefix used to identify this handler in error logs.
     */
    private const LOG_PREFIX = 'CouponJob';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var V3ImportStatusChecker
     */
    private $importStatusChecker;

    /**
     * @var ImporterItemStatusManager
     */
    private $importerItemStatusManager;

    /**
     * @var CouponJobReportBuilderInterface
     */
    private $couponJobReportBuilder;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Logger $logger
     * @param V3ImportStatusChecker $importStatusChecker
     * @param ImporterItemStatusManager $importerItemStatusManager
     * @param CouponJobReportBuilderInterface $couponJobReportBuilder
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Logger $logger,
        V3ImportStatusChecker $importStatusChecker,
        ImporterItemStatusManager $importerItemStatusManager,
        CouponJobReportBuilderInterface $couponJobReportBuilder,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->importStatusChecker = $importStatusChecker;
        $this->importerItemStatusManager = $importerItemStatusManager;
        $this->couponJobReportBuilder = $couponJobReportBuilder;
        $this->serializer = $serializer;
    }

    /**
     * Process.
     *
     * @param InProgressImportContext $context
     * @param ImporterCollection $items
     * @return int
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(InProgressImportContext $context, ImporterCollection $items): int
    {
        $itemsCount = 0;

        foreach ($items as $item) {
            try {
                $response = $this->importStatusChecker->check($item, $context, self::LOG_PREFIX);
            } catch (\Exception $e) {
                $this->importerItemStatusManager->markFailed($item, $e->getMessage());
                $this->importerItemStatusManager->save($item);
                $this->onStatusCheckFailure($item, $e);
                continue;
            }

            $itemsCount += $this->processResponse($response, $item);
        }

        return $itemsCount;
    }

    /**
     * Process the API response for a CouponJob importer row.
     *
     * On success: marks the importer row imported (message is cleared) and passes
     * the imported-contact count and per-contact failures directly to ReportBuilder,
     * bypassing the message column to avoid VARCHAR length limits.
     *
     * Note: storeContactIds is intentionally omitted — CouponJob contacts are
     * not Magento contacts and should not be written to the contact table.
     *
     * @param V3ImportInterface $response
     * @param ImporterModel $item
     * @return int  Returns 1 while the import is still in progress, 0 when done.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processResponse($response, $item)
    {
        $itemCount = 0;
        $currentBatchFailures = [];
        $recordsImported = 0;

        if ($response->getStatus() === 'Finished') {
            $item = $this->importerItemStatusManager->markImported($item);
            $currentBatchFailures = $this->extractContactFailures($response);
            $recordsImported = $this->extractSuccessCount($response);
        } elseif ($this->importerItemStatusManager->isFailedStatus($response->getStatus())) {
            $this->importerItemStatusManager->markFailed(
                $item,
                'Import failed with status ' . $response->getStatus()
            );
        } else {
            // Still in progress.
            $itemCount = 1;
        }

        $this->importerItemStatusManager->save($item);

        if ($itemCount === 0) {
            $couponJobId = $this->extractCouponJobId($item->getData('import_data'));
            if ($couponJobId) {
                $this->couponJobReportBuilder->build(
                    $couponJobId,
                    (int) $item->getId(),
                    $recordsImported,
                    $currentBatchFailures
                );
            }
        }

        return $itemCount;
    }

    /**
     * Reconcile the coupon job report when a batch fails during status polling.
     *
     * A failed status check marks the importer row FAILED and continues without
     * calling processResponse(), so ReportBuilder would otherwise never learn that
     * this batch resolved. Because a FAILED row is not re-polled, if this were the
     * last unresolved batch the coupon job would stay stuck in PROCESSING. Rebuild
     * the report here (with no imported records and no per-contact failures) so the
     * job can still reach a terminal state.
     *
     * @param ImporterModel $item
     * @param \Exception $e
     * @return void
     */
    private function onStatusCheckFailure(ImporterModel $item, \Exception $e): void
    {
        $couponJobId = $this->extractCouponJobId($item->getData('import_data'));
        if ($couponJobId) {
            $this->couponJobReportBuilder->build($couponJobId, (int) $item->getId(), 0, []);
        }
    }

    /**
     * Extract coupon_job_id from the importer row's serialized import_data.
     *
     * @param string|null $importData
     * @return int
     */
    private function extractCouponJobId(?string $importData): int
    {
        if (empty($importData)) {
            return 0;
        }
        try {
            $decoded = $this->serializer->unserialize($importData);
            return isset($decoded['coupon_job_id']) ? (int) $decoded['coupon_job_id'] : 0;
        } catch (\Exception $e) {
            $this->logger->error(
                'CouponJobInProgressImportResponseHandler: failed to extract coupon_job_id: ' . $e->getMessage()
            );
            return 0;
        }
    }

    /**
     * Extract the number of contacts successfully imported from the API response summary.
     *
     * @param V3ImportInterface $response
     * @return int
     */
    private function extractSuccessCount(V3ImportInterface $response): int
    {
        $summary = $response->getSummary();
        if (!$summary) {
            return 0;
        }

        return (int) $summary->getUpdatedContacts();
    }

    /**
     * Log any contacts that were newly created during the import.
     *
     * CouponJob batches should only update existing contacts. A new contact being
     * created means the contact had previously been deleted, so it is recorded as a
     * failure (failure code "Updated deleted contact") and logged so it can be
     * investigated. It is NOT counted as a successful import.
     *
     * @param V3ImportInterface $response
     * @return array<int, array{email: string, failure_code: string, description: string}>
     */
    private function extractCreatedContacts(V3ImportInterface $response): array
    {
        $failedAt = gmdate('Y-m-d H:i:s');
        $result = [];

        /** @var \Dotdigital\V3\Models\Contact\Import $response */
        $createdCollection = $response->getCreated();
        if (!$createdCollection || $createdCollection->count() === 0) {
            return $result;
        }

        foreach ($createdCollection->all() as $contact) {
            $identifiers = $contact->getIdentifiers();
            $email = $identifiers ? (string) $identifiers->getEmail() : '';
            $result[] = [
                'email'        => $email,
                'failure_code' => 'Updated deleted contact',
                'description'  => 'Contact was previously deleted and has been recreated by this import.',
                'failed_at' => $failedAt
            ];
        }

        return $result;
    }

    /**
     * Extract per-contact failures from the API response.
     *
     * Includes both the API-reported per-contact failures and any contacts that were
     * unexpectedly (re)created during the import (see extractCreatedContacts). Each
     * entry represents one failure reason for one contact; a single contact with
     * multiple failure details produces multiple entries.
     *
     * @param V3ImportInterface $response
     * @return array<int, array{email: string, failure_code: string, description: string}>
     */
    private function extractContactFailures(V3ImportInterface $response): array
    {
        $failedAt = gmdate('Y-m-d H:i:s');
        $result = $this->extractCreatedContacts($response);

        $failureCollection = $response->getFailures();
        if (!$failureCollection) {
            return $result;
        }

        foreach ($failureCollection->all() as $failure) {
            $identifiers = $failure->getIdentifiers();
            $email = $identifiers ? (string) $identifiers->getEmail() : '';
            if ($email === '') {
                continue;
            }

            $failureDetails = $failure->getFailures();
            if ($failureDetails) {
                foreach ($failureDetails->all() as $detail) {
                    $failureCode = (string) $detail->getFailureCode();
                    $description = (string) $detail->getDescription();
                    $result[] = [
                        'email'        => $email,
                        'failure_code' => $failureCode,
                        'description'  => $description,
                        'failed_at' => $failedAt
                    ];
                }
            } else {
                $result[] = [
                    'email'        => $email,
                    'failure_code' => '',
                    'description'  => '',
                    'failed_at' => $failedAt
                ];
            }
        }

        return $result;
    }
}
