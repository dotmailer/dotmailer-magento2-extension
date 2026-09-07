<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Queue\Coupon;

use Dotdigital\Enums\DataField\Type;
use Dotdigital\Enums\DataField\Visibility;
use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\FailureLogInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJobFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V2\ClientFactory as V2ClientFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponStartData;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigital\V3\Utility\Pagination\ParameterCollection;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\StringUtils;

class CouponStartConsumer
{
    private const API_PAGE_LIMIT = 5000;
    private const DATA_FIELD_NAME_LENGTH_LIMIT = 20;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CouponJobFactory
     */
    private $couponJobFactory;

    /**
     * @var CouponJobResource
     */
    private $couponJobResource;

    /**
     * @var CouponSyncPublisher
     */
    private $couponSyncPublisher;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var V2ClientFactory
     */
    private $v2ClientFactory;

    /**
     * @var StringUtils
     */
    private $stringUtils;

    /**
     * @var ReportInterfaceFactory
     */
    private $reportFactory;

    /**
     * @var FailureLogInterface
     */
    private $failureLog;

    /**
     * @var JobUpdaterInterface
     */
    private $jobUpdater;

    /**
     * @param Logger $logger
     * @param CouponJobFactory $couponJobFactory
     * @param CouponJobResource $couponJobResource
     * @param CouponSyncPublisher $couponSyncPublisher
     * @param ClientFactory $clientFactory
     * @param Json $serializer
     * @param V2ClientFactory $v2ClientFactory
     * @param StringUtils $stringUtils
     * @param ReportInterfaceFactory $reportFactory
     * @param FailureLogInterface $failureLog
     * @param JobUpdaterInterface $jobUpdater
     */
    public function __construct(
        Logger $logger,
        CouponJobFactory $couponJobFactory,
        CouponJobResource $couponJobResource,
        CouponSyncPublisher $couponSyncPublisher,
        ClientFactory $clientFactory,
        Json $serializer,
        V2ClientFactory $v2ClientFactory,
        StringUtils $stringUtils,
        ReportInterfaceFactory $reportFactory,
        FailureLogInterface $failureLog,
        JobUpdaterInterface $jobUpdater
    ) {
        $this->logger = $logger;
        $this->couponJobFactory = $couponJobFactory;
        $this->couponJobResource = $couponJobResource;
        $this->couponSyncPublisher = $couponSyncPublisher;
        $this->clientFactory = $clientFactory;
        $this->serializer = $serializer;
        $this->v2ClientFactory = $v2ClientFactory;
        $this->stringUtils = $stringUtils;
        $this->reportFactory = $reportFactory;
        $this->failureLog = $failureLog;
        $this->jobUpdater = $jobUpdater;
    }

    /**
     * Process a coupon start message.
     *
     * Thin guard around runJob(): any unhandled throwable (including PHP Errors
     * such as a TypeError from the cURL layer) is caught here so the coupon job
     * is always moved to FAILED rather than left stuck in its previous status.
     *
     * @param CouponStartData $couponStartData
     * @return void
     */
    public function process(CouponStartData $couponStartData): void
    {
        $jobId = $couponStartData->getJobId();

        try {
            $this->runJob($couponStartData);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'CouponStartConsumer: Unhandled error for job %d: %s',
                $jobId,
                $e->getMessage()
            ));
            $this->failJob($jobId, $e->getMessage());
        }
    }

    /**
     * Run the coupon start workflow.
     *
     * Paginates through Dotdigital contacts for the selected cohort and dispatches
     * one ddg.coupon.sync message per batch of contacts.
     *
     * @param CouponStartData $couponStartData
     * @return void
     */
    private function runJob(CouponStartData $couponStartData): void
    {
        $jobId = $couponStartData->getJobId();
        $websiteId = $couponStartData->getWebsiteId();

        $couponJob = $this->couponJobFactory->create();
        $this->couponJobResource->load($couponJob, $jobId);

        if (!$couponJob->getId()) {
            $this->logger->error(sprintf('CouponStartConsumer: CouponJob %d not found.', $jobId));
            return;
        }

        if ((string) $couponJob->getData('status') === CouponJobInterface::STATUS_CANCELLED) {
            $this->logger->info(sprintf(
                'CouponStartConsumer: Skipping job %d; job is cancelled.',
                $jobId
            ));
            return;
        }

        $salesRuleId = (int) $couponJob->getData('sales_rule_id');
        $filterType = $couponStartData->getFilterType();
        $filterId = $couponStartData->getFilterId();
        $filterParam = $filterType === 'LIST' ? '~listId' : '~segmentId';
        $batchSize = $couponStartData->getBatchSize();
        $dataField = $couponStartData->getDataField();

        try {
            $this->ensureDatafieldExists($websiteId, $dataField);
        } catch (LocalizedException $e) {
            $this->logger->error(
                sprintf('CouponStartConsumer: Error ensuring data field exists: %s', $e->getMessage()),
            );
            $this->failJob($jobId, $e->getMessage());
            return;
        }

        $marker = '';
        $emailCollection = [];
        $client = $this->clientFactory->create(['data' => ['websiteId' => $websiteId]]);

        do {
            try {
                $parameterCollection = new ParameterCollection();
                $parameterCollection->setParam($filterParam, $filterId);
                $parameterCollection->setParam('limit', self::API_PAGE_LIMIT);
                if ($marker) {
                    $parameterCollection->setParam('marker', $marker);
                }
                $response = $client->contacts->getContacts($parameterCollection);
                $parsedResponse = $this->parseContactsResponse($response);
            } catch (ResponseValidationException $e) {
                $this->logger->error(
                    sprintf('CouponStartConsumer: Error fetching contacts: %s', $e->getMessage()),
                    [$e->getDetails()]
                );
                $this->failJob($jobId, $e->getMessage());
                return;
            } catch (\Http\Client\Exception|\Exception $e) {
                $this->logger->error(
                    sprintf('CouponStartConsumer: Error fetching contacts: %s', $e->getMessage()),
                );
                $this->failJob($jobId, $e->getMessage());
                return;
            }

            $emailCollection = array_merge($emailCollection, $parsedResponse['emails']);
            $marker = $parsedResponse['marker'];

        } while (!empty($marker));

        $totalRecords = count($emailCollection);

        if ($totalRecords === 0) {
            $this->logger->info(sprintf('CouponStartConsumer: No contacts found for job %d.', $jobId));
            $this->failJob($jobId, sprintf('No contacts found for job %d.', $jobId));
            return;
        }

        $emailBatches = array_chunk($emailCollection, $batchSize);
        $totalBatches = count($emailBatches);

        if ($this->couponJobResource->getStatusById($jobId) === CouponJobInterface::STATUS_CANCELLED) {
            $this->logger->info(sprintf(
                'CouponStartConsumer: Skipping dispatch for job %d; job was cancelled during contact retrieval.',
                $jobId
            ));
            return;
        }

        // Seed the report with known totals before any batches are polled.
        $this->initialiseJobReport($jobId, $totalRecords, $totalBatches);

        foreach ($emailBatches as $emailBatch) {
            $this->couponSyncPublisher->publish(
                $jobId,
                $websiteId,
                $salesRuleId,
                $emailBatch,
                $totalBatches,
                $couponStartData->getCodeFormat(),
                $couponStartData->getCodePrefix(),
                $couponStartData->getCodeSuffix(),
                $couponStartData->getCodeLength(),
                $couponStartData->getCodeDash(),
                $couponStartData->getExpiresAt(),
                $couponStartData->getDataField()
            );
        }

        $this->updateCouponJobStatus($jobId, CouponJobInterface::STATUS_PROCESSING);

        $this->logger->info(sprintf(
            'CouponStartConsumer: Dispatched %d sync batches for job %d (%d contacts total).',
            $totalBatches,
            $jobId,
            $totalRecords
        ));
    }

    /**
     * Update the coupon job status under a row lock.
     *
     * @param int $jobId
     * @param string $status
     * @return void
     */
    private function updateCouponJobStatus(int $jobId, string $status): void
    {
        $this->jobUpdater->update($jobId, function (CouponJob $couponJob) use ($status) {
            $couponJob->setStatus($status);
        });
    }

    /**
     * Parse the contacts API response into an array.
     *
     * @param string $response
     * @return array
     */
    private function parseContactsResponse(string $response): array
    {
        $parsedResponse = ['emails' => [], 'marker' => ''];
        $response = $this->serializer->unserialize($response);

        if (isset($response['_items'])) {
            $parsedResponse['emails'] = $this->extractEmails($response['_items']);
        }
        if (isset($response['_links']['next']['marker'])) {
            $parsedResponse['marker'] = $response['_links']['next']['marker'];
        }

        return $parsedResponse;
    }

    /**
     * Extract email addresses from the contacts array.
     *
     * @param array $contacts
     * @return string[]
     */
    private function extractEmails(array $contacts): array
    {
        $emails = [];
        foreach ($contacts as $contact) {
            $email = $contact['identifiers']['email'] ?? $contact['email'] ?? null;
            if ($email) {
                $emails[] = $email;
            }
        }
        return array_values(array_filter($emails));
    }

    /**
     * Seed the job report with total_records and total_batches.
     *
     * Ensures the report has a denominator as soon as batches start resolving.
     *
     * @param int $jobId
     * @param int $totalRecords
     * @param int $totalBatches
     * @return void
     */
    private function initialiseJobReport(int $jobId, int $totalRecords, int $totalBatches): void
    {
        $this->jobUpdater->update($jobId, function (CouponJob $couponJob) use ($totalRecords, $totalBatches) {
            /** @var \Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report $report */
            $report = $this->reportFactory->create();
            $report->setData(ReportInterface::TOTAL_RECORDS, $totalRecords);
            $report->setData(ReportInterface::TOTAL_BATCHES, $totalBatches);
            $report->setData(ReportInterface::TOTAL_RECORDS_IMPORTED, 0);
            $report->setData(ReportInterface::TOTAL_BATCHES_PROCESSED, 0);
            $report->setData(ReportInterface::BATCH_RECORDS_IMPORTED, []);
            $couponJob->updateReport($report);
        });
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
            'id'            => uniqid('coupon_start_', true),
            'error_message' => $errorMessage,
            'failed_at'     => $failedAt,
        ]]);

        $this->jobUpdater->update($jobId, function (CouponJob $couponJob) {
            $couponJob->incrementFailedMessagesCount();
            $couponJob->setStatus(CouponJobInterface::STATUS_FAILED);
        });
    }

    /**
     * Ensures a valid data field is selected.
     *
     * If the field does not exist it is created as a String/Private field.
     *
     * @param int $websiteId
     * @param string $rawName
     * @return string The normalised datafield name
     * @throws LocalizedException
     */
    private function ensureDatafieldExists(int $websiteId, string $rawName): string
    {
        $name = strtoupper(str_replace(' ', '_', trim($rawName)));

        if ($this->stringUtils->strlen($name) > self::DATA_FIELD_NAME_LENGTH_LIMIT) {
            throw new LocalizedException(__(
                'Data field name "%1" exceeds the character limit after normalisation.',
                $name
            ));
        }

        $client = $this->v2ClientFactory->create(['data' => ['websiteId' => $websiteId]]);

        try {
            $existingFields = $client->dataFields->show()->getList();
        } catch (\Http\Client\Exception|\Exception $e) {
            throw new LocalizedException(__('Error retrieving data fields: %1', $e->getMessage()));
        }

        foreach ($existingFields as $existingField) {
            if (strtoupper($existingField->getName()) === $name) {
                return $name;
            }
        }

        try {
            /** @var Type $type */
            $type = Type::from(Type::STRING);
            /** @var Visibility $visibility */
            $visibility = Visibility::from(Visibility::PRIVATE);

            $client->dataFields->create($name, $type, $visibility);
        } catch (\Http\Client\Exception|\Exception $e) {
            throw new LocalizedException(__(
                'Could not create data field "%1": %2',
                $name,
                $e->getMessage()
            ));
        }

        return $name;
    }
}
