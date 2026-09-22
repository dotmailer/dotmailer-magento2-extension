<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Coupon;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigital\V3\Models\ContactCollection as DotdigitalContactCollection;
use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\FailureLogInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponSyncData;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponGenerator;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as SalesRuleResource;

class CouponSyncConsumer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var SalesRuleResource
     */
    private $salesRuleResource;

    /**
     * @var DotdigitalCouponGenerator
     */
    private $couponGenerator;

    /**
     * @var MegaBatchProcessor
     */
    private $megaBatchProcessor;

    /**
     * @var FailureLogInterface
     */
    private $failureLog;

    /**
     * @var JobUpdaterInterface
     */
    private $jobUpdater;

    /**
     * @var CouponJobResource
     */
    private $couponJobResource;

    /**
     * @param Logger $logger
     * @param RuleFactory $ruleFactory
     * @param SalesRuleResource $salesRuleResource
     * @param DotdigitalCouponGenerator $couponGenerator
     * @param MegaBatchProcessor $megaBatchProcessor
     * @param FailureLogInterface $failureLog
     * @param JobUpdaterInterface $jobUpdater
     * @param CouponJobResource $couponJobResource
     */
    public function __construct(
        Logger $logger,
        RuleFactory $ruleFactory,
        SalesRuleResource $salesRuleResource,
        DotdigitalCouponGenerator $couponGenerator,
        MegaBatchProcessor $megaBatchProcessor,
        FailureLogInterface $failureLog,
        JobUpdaterInterface $jobUpdater,
        CouponJobResource $couponJobResource
    ) {
        $this->logger = $logger;
        $this->ruleFactory = $ruleFactory;
        $this->salesRuleResource = $salesRuleResource;
        $this->couponGenerator = $couponGenerator;
        $this->megaBatchProcessor = $megaBatchProcessor;
        $this->failureLog = $failureLog;
        $this->jobUpdater = $jobUpdater;
        $this->couponJobResource = $couponJobResource;
    }

    /**
     * Process a coupon sync message.
     *
     * Thin guard around runJob(): any unhandled throwable (including PHP Errors)
     * is caught here so the coupon job records the failure and is moved to FAILED
     * rather than leaving the batch silently unaccounted for.
     *
     * @param CouponSyncData $couponSyncData
     * @return void
     */
    public function process(CouponSyncData $couponSyncData): void
    {
        $jobId = $couponSyncData->getJobId();

        try {
            $this->runJob($couponSyncData);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'CouponSyncConsumer: Unhandled error for job %d: %s',
                $jobId,
                $e->getMessage()
            ));
            $this->failJob($jobId, $e->getMessage());
        }
    }

    /**
     * Run the coupon sync workflow.
     *
     * For each email, generates a coupon and builds an SDK contact with the coupon
     * code set as a data-field. Then posts the batch via MegaBatchProcessor.
     *
     * @param CouponSyncData $couponSyncData
     * @return void
     * @throws LocalizedException
     */
    private function runJob(CouponSyncData $couponSyncData): void
    {
        $jobId = $couponSyncData->getJobId();
        $websiteId = $couponSyncData->getWebsiteId();
        $salesRuleId = $couponSyncData->getSalesRuleId();
        $emails = $couponSyncData->getEmails();
        $dataField = $couponSyncData->getDataField();

        // if the job has already reached a terminal state, skip this batch entirely.
        $terminalStatus = $this->getTerminalStatus($jobId);
        if ($terminalStatus !== null) {
            $this->logger->info(sprintf(
                'CouponSyncConsumer: Skipping batch %d for job %d; job already marked as %s.',
                $couponSyncData->getBatchNumber(),
                $jobId,
                $terminalStatus
            ));
            return;
        }

        $rule = $this->ruleFactory->create();
        $this->salesRuleResource->load($rule, $salesRuleId);

        if (!$rule->getId()) {
            $message = sprintf('Sales rule %d not found for job %d.', $salesRuleId, $jobId);
            $this->logger->error('CouponSyncConsumer: ' . $message);
            $this->failJob($jobId, $message);
            return;
        }

        $batch = [];
        foreach ($emails as $email) {
            try {
                $couponCode = $this->couponGenerator->generateCoupon(
                    $rule,
                    $couponSyncData->getCodeFormat(),
                    $couponSyncData->getCodePrefix(),
                    $couponSyncData->getCodeSuffix(),
                    $email,
                    null,
                    $couponSyncData->getCodeLength(),
                    $couponSyncData->getCodeDash(),
                    $couponSyncData->getExpiresAt()
                );

                $contact = new SdkContact();
                $contact->setMatchIdentifier('email');
                $contact->setIdentifiers(['email' => $email]);
                $contact->setDataFields([$dataField => $couponCode]);
                $batch[$email] = $contact;

            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'CouponSyncConsumer: Failed to generate coupon for %s in job %d: %s',
                    $email,
                    $jobId,
                    $e->getMessage()
                ));
            }
        }

        if (empty($batch)) {
            $message = sprintf(
                'No coupons generated for batch %d in job %d.',
                $couponSyncData->getBatchNumber(),
                $jobId
            );
            $this->logger->warning('CouponSyncConsumer: ' . $message);
            $this->failJob($jobId, $message);
            return;
        }

        $this->megaBatchProcessor->process(
            ['coupon_job_id' => $jobId, 'records' => $batch],
            $websiteId,
            Importer::IMPORT_TYPE_COUPON_JOB
        );

        $this->logger->info(sprintf(
            'CouponSyncConsumer: Importer batch %d created for job %d (%d contacts).',
            $couponSyncData->getBatchNumber(),
            $jobId,
            count($batch)
        ));
    }

    /**
     * Record a consumer error and mark the job FAILED.
     *
     * @param int $jobId
     * @param string $errorMessage
     * @return void
     */
    private function failJob(int $jobId, string $errorMessage): void
    {
        $failedAt = gmdate('Y-m-d H:i:s');
        $this->failureLog->append($jobId, FailureLogInterface::CATEGORY_MESSAGES, [[
            'id'            => uniqid('coupon_sync_', true),
            'error_message' => $errorMessage,
            'failed_at'     => $failedAt,
        ]]);

        $this->jobUpdater->update($jobId, function (CouponJob $couponJob) {
            $couponJob->incrementFailedMessagesCount();
            $couponJob->setStatus(CouponJobInterface::STATUS_FAILED);
        });
    }

    /**
     * Return the linked coupon job's status when it has already reached a terminal state.
     *
     * Uses a cheap, indexed status read so a failed, complete or cancelled job
     * short-circuits every remaining queued batch without loading the full model
     * or acquiring a lock.
     *
     * @param int $jobId
     * @return string|null The terminal status, or null when the job may still be processed.
     */
    private function getTerminalStatus(int $jobId): ?string
    {
        $status = $this->couponJobResource->getStatusById($jobId);

        return in_array($status, CouponJobInterface::TERMINAL_STATUSES, true) ? $status : null;
    }
}
