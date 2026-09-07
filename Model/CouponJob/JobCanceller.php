<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobCancellerInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\ReportBuilderInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory as ImporterCollectionFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Cancels a bulk coupon job.
 *
 * The status is written first, under JobUpdater's row lock, so that any consumer
 * or importer stage reading the status afterwards early-exits instead of starting
 * new work. Only then are the batches that were never sent failed, and the report
 * reconciled.
 *
 * Batches already accepted by Dotdigital are deliberately left in flight. They keep
 * polling to completion so the merchant sees an accurate picture of what actually
 * reached the platform; each one folds its real numbers into the report via
 * ReportBuilder::build() as it finishes. The refresh() below is therefore a
 * point-in-time snapshot, not the final word.
 */
class JobCanceller implements JobCancellerInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CouponJobResource
     */
    private $couponJobResource;

    /**
     * @var JobUpdaterInterface
     */
    private $jobUpdater;

    /**
     * @var ImporterCollectionFactory
     */
    private $importerCollectionFactory;

    /**
     * @var ReportBuilderInterface
     */
    private $reportBuilder;

    /**
     * @param Logger $logger
     * @param CouponJobResource $couponJobResource
     * @param JobUpdaterInterface $jobUpdater
     * @param ImporterCollectionFactory $importerCollectionFactory
     * @param ReportBuilderInterface $reportBuilder
     */
    public function __construct(
        Logger $logger,
        CouponJobResource $couponJobResource,
        JobUpdaterInterface $jobUpdater,
        ImporterCollectionFactory $importerCollectionFactory,
        ReportBuilderInterface $reportBuilder
    ) {
        $this->logger = $logger;
        $this->couponJobResource = $couponJobResource;
        $this->jobUpdater = $jobUpdater;
        $this->importerCollectionFactory = $importerCollectionFactory;
        $this->reportBuilder = $reportBuilder;
    }

    /**
     * @inheritDoc
     */
    public function cancel(int $couponJobId): bool
    {
        if (!$this->isCancellable($this->couponJobResource->getStatusById($couponJobId))) {
            return false;
        }

        if (!$this->writeCancelledStatus($couponJobId)) {
            return false;
        }

        $failedBatches = $this->couponJobResource->failUnsentBatches(
            $couponJobId,
            self::CANCELLED_MESSAGE
        );

        $this->logger->info(sprintf(
            'CouponJob JobCanceller: job %d cancelled; %d unsent batch(es) failed. '
            . 'Batches already accepted by Dotdigital will continue to be processed.',
            $couponJobId,
            $failedBatches
        ));

        if ($this->hasBatches($couponJobId)) {
            $this->reportBuilder->refresh($couponJobId);
        }

        return true;
    }

    /**
     * Move the job to the cancelled status under a row lock.
     *
     * The status is re-checked inside the lock so a job that raced to a terminal
     * state in the meantime is left untouched.
     *
     * @param int $couponJobId
     * @return bool
     */
    private function writeCancelledStatus(int $couponJobId): bool
    {
        return $this->jobUpdater->update($couponJobId, function (CouponJob $couponJob) use ($couponJobId) {
            if (!$this->isCancellable((string) $couponJob->getData('status'))) {
                throw new LocalizedException(__(
                    'Coupon job %1 is no longer in a cancellable state.',
                    $couponJobId
                ));
            }

            $couponJob->setStatus(CouponJobInterface::STATUS_CANCELLED);
        });
    }

    /**
     * Check whether a status may still be cancelled.
     *
     * @param string|null $status
     * @return bool
     */
    private function isCancellable(?string $status): bool
    {
        return in_array($status, CouponJobInterface::CANCELLABLE_STATUSES, true);
    }

    /**
     * Check whether the job has any linked importer batches.
     *
     * @param int $couponJobId
     * @return bool
     */
    private function hasBatches(int $couponJobId): bool
    {
        return $this->importerCollectionFactory->create()
            ->getBatchesByCouponJobId($couponJobId)
            ->getSize() > 0;
    }
}
