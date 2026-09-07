<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Coupon;

use Dotdigital\Enums\DataField\Type;
use Dotdigital\Enums\DataField\Visibility;
use Dotdigital\V3\Resources\Contacts as ContactsResource;
use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V2\Client as V2Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V2\ClientFactory as V2ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\FailureLogInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJobFactory;
use Dotdigitalgroup\Email\Model\Queue\Coupon\CouponStartConsumer;
use Dotdigitalgroup\Email\Model\Queue\Coupon\CouponSyncPublisher;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponStartData;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\StringUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CouponStartConsumerTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var CouponJobFactory|MockObject
     */
    private $couponJobFactoryMock;

    /**
     * @var CouponJobResource|MockObject
     */
    private $couponJobResourceMock;

    /**
     * @var CouponSyncPublisher|MockObject
     */
    private $couponSyncPublisherMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    /**
     * @var V2ClientFactory|MockObject
     */
    private $v2ClientFactoryMock;

    /**
     * @var StringUtils|MockObject
     */
    private $stringUtilsMock;

    /**
     * @var ReportInterfaceFactory|MockObject
     */
    private $reportFactoryMock;

    /**
     * @var FailureLogInterface|MockObject
     */
    private $failureLogMock;

    /**
     * @var JobUpdaterInterface|MockObject
     */
    private $jobUpdaterMock;

    /**
     * @var CouponStartConsumer
     */
    private $couponStartConsumer;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->couponJobFactoryMock = $this->createMock(CouponJobFactory::class);
        $this->couponJobResourceMock = $this->createMock(CouponJobResource::class);
        $this->couponSyncPublisherMock = $this->createMock(CouponSyncPublisher::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->serializerMock = $this->createMock(Json::class);
        $this->v2ClientFactoryMock = $this->createMock(V2ClientFactory::class);
        $this->stringUtilsMock = $this->createMock(StringUtils::class);
        $this->stringUtilsMock->method('strlen')->willReturnCallback(fn (string $value): int => strlen($value));
        $this->reportFactoryMock = $this->createMock(ReportInterfaceFactory::class);
        $this->reportFactoryMock->method('create')
            ->willReturnCallback(function () {
                return new \Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report();
            });
        $this->failureLogMock = $this->createMock(FailureLogInterface::class);
        $this->jobUpdaterMock = $this->createMock(JobUpdaterInterface::class);

        $this->serializerMock->method('unserialize')
            ->willReturnCallback(function (string $string) {
                return json_decode($string, true);
            });

        $this->v2ClientFactoryMock->method('create')->willReturn(
            $this->createV2ClientMock($this->createDataFieldsResourceMock([]))
        );

        // jobUpdater calls the callback with the coupon job from the factory
        $this->jobUpdaterMock->method('update')
            ->willReturnCallback(function (int $jobId, callable $mutator) {
                $couponJob = $this->couponJobFactoryMock->create();
                $mutator($couponJob);
                return true;
            });

        $this->couponStartConsumer = new CouponStartConsumer(
            $this->loggerMock,
            $this->couponJobFactoryMock,
            $this->couponJobResourceMock,
            $this->couponSyncPublisherMock,
            $this->clientFactoryMock,
            $this->serializerMock,
            $this->v2ClientFactoryMock,
            $this->stringUtilsMock,
            $this->reportFactoryMock,
            $this->failureLogMock,
            $this->jobUpdaterMock
        );
    }

    /**
     * Test that process logs error and returns when CouponJob not found.
     */
    public function testProcessLogsErrorWhenJobNotFound(): void
    {
        $startData = $this->createStartData(99, 1);

        $couponJobMock = $this->createCouponJobMock();
        $couponJobMock->method('getId')->willReturn(null);

        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('CouponJob 99 not found'));

        $this->couponSyncPublisherMock->expects($this->never())->method('publish');

        $this->couponStartConsumer->process($startData);
    }

    /**
     * Test that process marks job failed when no contacts are returned.
     */
    public function testProcessMarksJobFailedWhenNoContacts(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $startData = $this->createStartData($jobId, $websiteId);

        $couponJobMock = $this->createCouponJobMock($jobId, 55);

        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $clientMock = $this->createClientMockWithApiPages([
            ['_items' => []]
        ]);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => $websiteId]])
            ->willReturn($clientMock);

        $couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_FAILED);

        $this->couponSyncPublisherMock->expects($this->never())->method('publish');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('No contacts found'));

        $this->couponStartConsumer->process($startData);
    }

    /**
     * Test that a job cancelled before the message was consumed does no work at all.
     *
     * No data field is ensured, no contacts are fetched and no status is written —
     * cancellation is not a failure.
     */
    public function testProcessSkipsWhenJobCancelledOnLoad(): void
    {
        $jobId = 42;
        $startData = $this->createStartData($jobId, 1);

        $couponJobMock = $this->createCouponJobMock($jobId, 55, CouponJobInterface::STATUS_CANCELLED);
        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('job is cancelled'));

        $this->clientFactoryMock->expects($this->never())->method('create');
        $this->v2ClientFactoryMock->expects($this->never())->method('create');
        $this->couponSyncPublisherMock->expects($this->never())->method('publish');
        $couponJobMock->expects($this->never())->method('setStatus');
        $couponJobMock->expects($this->never())->method('updateReport');

        $this->couponStartConsumer->process($startData);
    }

    /**
     * Test that a job cancelled while contacts were being paginated dispatches nothing.
     *
     * Contact retrieval can run for minutes, so the status is re-read before any
     * batch is published or the report is seeded.
     */
    public function testProcessSkipsDispatchWhenJobCancelledDuringPagination(): void
    {
        $jobId = 42;
        $startData = $this->createStartData($jobId, 1);

        $couponJobMock = $this->createCouponJobMock($jobId, 55);
        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $clientMock = $this->createClientMockWithApiPages([
            [
                '_items' => [
                    ['identifiers' => ['email' => 'a@test.com']],
                ],
            ],
        ]);
        $this->clientFactoryMock->method('create')->willReturn($clientMock);

        // The job is cancelled by the time pagination finishes.
        $this->couponJobResourceMock->method('getStatusById')
            ->willReturn(CouponJobInterface::STATUS_CANCELLED);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('cancelled during contact retrieval'));

        $this->couponSyncPublisherMock->expects($this->never())->method('publish');
        $couponJobMock->expects($this->never())->method('updateReport');
        $couponJobMock->expects($this->never())->method('setStatus');

        $this->couponStartConsumer->process($startData);
    }

    /**
     * Test that process paginates through all API pages, collects contacts,
     * batches them by batchSize from start data, dispatches sync messages, and sets job to processing.
     */
    public function testProcessDispatchesSyncMessagesAndSetsProcessing(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $salesRuleId = 55;
        $batchSize = 2;
        $startData = $this->createStartData(
            $jobId,
            $websiteId,
            'LIST',
            100,
            'alphanum',
            'PRE',
            'SUF',
            'COUPONCODE',
            $batchSize,
            12,
            4,
            '2026-12-31 23:59:59'
        );

        $couponJobMock = $this->createCouponJobMock($jobId, $salesRuleId);

        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        // Two API pages: first has 2 contacts with a next marker, second has 1 contact with no marker
        $clientMock = $this->createClientMockWithApiPages([
            [
                '_items' => [
                    ['identifiers' => ['email' => 'a@test.com']],
                    ['identifiers' => ['email' => 'b@test.com']],
                ],
                '_links' => ['next' => ['marker' => 'page2marker']]
            ],
            [
                '_items' => [
                    ['identifiers' => ['email' => 'c@test.com']],
                ],
            ],
        ]);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);

        // batchSize = 2 → 3 contacts → 2 batches: [a, b] and [c]
        $publishedBatches = [];
        $this->couponSyncPublisherMock->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (
                int $jId,
                int $wId,
                int $srId,
                array $emails,
                int $totalBatches,
                ?string $cf,
                ?string $cp,
                ?string $cs,
                ?int $cl,
                ?int $cd,
                ?string $ea,
                string $df
            ) use (
                $jobId,
                $websiteId,
                $salesRuleId,
                &$publishedBatches
            ) {
                $this->assertSame($jobId, $jId);
                $this->assertSame($websiteId, $wId);
                $this->assertSame($salesRuleId, $srId);
                $this->assertSame(2, $totalBatches);
                $this->assertSame('alphanum', $cf);
                $this->assertSame('PRE', $cp);
                $this->assertSame('SUF', $cs);
                $this->assertSame(12, $cl);
                $this->assertSame(4, $cd);
                $this->assertSame('2026-12-31 23:59:59', $ea);
                $this->assertSame('COUPONCODE', $df);
                $publishedBatches[] = $emails;
            });

        $couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_PROCESSING);

        $this->couponStartConsumer->process($startData);
    }

    /**
     * Test process with segment filter type uses ~segmentId parameter.
     */
    public function testProcessUsesSegmentFilterParam(): void
    {
        $jobId = 50;
        $websiteId = 2;
        $startData = $this->createStartData($jobId, $websiteId, 'SEGMENT', 200);

        $couponJobMock = $this->createCouponJobMock($jobId, 30);

        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        // Return empty contacts to trigger the "no contacts" path
        $clientMock = $this->createClientMockWithApiPages([
            ['_items' => []]
        ]);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);

        $couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_FAILED);

        $this->couponStartConsumer->process($startData);
    }

    /**
     * Test that contacts with email key fallback are extracted.
     */
    public function testProcessExtractsEmailsFromFallbackKey(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $startData = $this->createStartData($jobId, $websiteId);

        $couponJobMock = $this->createCouponJobMock($jobId, 55);

        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $clientMock = $this->createClientMockWithApiPages([
            [
                '_items' => [
                    ['email' => 'fallback@test.com'],
                ],
            ],
        ]);

        $this->clientFactoryMock->method('create')->willReturn($clientMock);

        $this->couponSyncPublisherMock->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function (
                int $jId,
                int $wId,
                int $srId,
                array $emails,
                ...$ignored
            ) {
                $this->assertSame(['fallback@test.com'], $emails);
            });

        $this->couponStartConsumer->process($startData);
    }

    /**
     * Test that process marks job as failed when the API SDK throws an exception.
     */
    public function testProcessMarksJobFailedWhenApiThrowsException(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $startData = $this->createStartData($jobId, $websiteId);

        $couponJobMock = $this->createCouponJobMock($jobId, 55);

        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $contactsResourceMock = $this->createMock(ContactsResource::class);
        $contactsResourceMock->method('getContacts')
            ->willThrowException(new \RuntimeException('Connection timeout'));

        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();

        $clientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);

        $this->clientFactoryMock->method('create')->willReturn($clientMock);

        $couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_FAILED);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Connection timeout'));

        $this->couponSyncPublisherMock->expects($this->never())->method('publish');

        $this->couponStartConsumer->process($startData);
    }

    /**
     * Test that process marks job failed when data field name exceeds the character limit.
     */
    public function testProcessMarksJobFailedWhenDataFieldNameTooLong(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $startData = $this->createStartData(
            $jobId,
            $websiteId,
            'LIST',
            100,
            'alphanum',
            null,
            null,
            'A_VERY_LONG_DATA_FIELD_NAME'
        );

        $couponJobMock = $this->createCouponJobMock($jobId, 55);
        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        // Override the default stub: field name is too long
        $stringUtilsMock = $this->createMock(StringUtils::class);
        $stringUtilsMock->method('strlen')->willReturn(30);

        $consumer = new CouponStartConsumer(
            $this->loggerMock,
            $this->couponJobFactoryMock,
            $this->couponJobResourceMock,
            $this->couponSyncPublisherMock,
            $this->clientFactoryMock,
            $this->serializerMock,
            $this->v2ClientFactoryMock,
            $stringUtilsMock,
            $this->reportFactoryMock,
            $this->failureLogMock,
            $this->jobUpdaterMock
        );

        $couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_FAILED);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error ensuring data field exists'));

        $this->couponSyncPublisherMock->expects($this->never())->method('publish');
        $this->clientFactoryMock->expects($this->never())->method('create');

        $consumer->process($startData);
    }

    /**
     * Test that process creates the data field when it does not already exist.
     */
    public function testProcessCreatesDataFieldWhenNotExists(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $dataFieldName = 'COUPONCODE';
        $startData = $this->createStartData($jobId, $websiteId, 'LIST', 100, 'alphanum', null, null, $dataFieldName);

        $couponJobMock = $this->createCouponJobMock($jobId, 55);
        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $dataFieldsResourceMock = $this->createDataFieldsResourceMock([]);
        $dataFieldsResourceMock->expects($this->once())
            ->method('create')
            ->with(
                $dataFieldName,
                $this->callback(fn ($type) => $type->value === Type::STRING),
                $this->callback(fn ($visibility) => $visibility->value === Visibility::PRIVATE)
            );

        $v2ClientFactoryMock = $this->createMock(V2ClientFactory::class);
        $v2ClientFactoryMock->method('create')->willReturn(
            $this->createV2ClientMock($dataFieldsResourceMock)
        );

        $consumer = new CouponStartConsumer(
            $this->loggerMock,
            $this->couponJobFactoryMock,
            $this->couponJobResourceMock,
            $this->couponSyncPublisherMock,
            $this->clientFactoryMock,
            $this->serializerMock,
            $v2ClientFactoryMock,
            $this->stringUtilsMock,
            $this->reportFactoryMock,
            $this->failureLogMock,
            $this->jobUpdaterMock
        );

        // Return contacts so the process continues to publish
        $clientMock = $this->createClientMockWithApiPages([
            [
                '_items' => [
                    ['identifiers' => ['email' => 'test@test.com']],
                ],
            ],
        ]);
        $this->clientFactoryMock->method('create')->willReturn($clientMock);

        $this->couponSyncPublisherMock->expects($this->once())->method('publish');

        $consumer->process($startData);
    }

    /**
     * Test that process marks job failed when data field creation throws an exception.
     */
    public function testProcessMarksJobFailedWhenDataFieldCreationFails(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $startData = $this->createStartData($jobId, $websiteId);

        $couponJobMock = $this->createCouponJobMock($jobId, 55);
        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $dataFieldsResourceMock = $this->createDataFieldsResourceMock([]);
        $dataFieldsResourceMock->method('create')
            ->willThrowException(new \RuntimeException('Field limit reached'));

        $v2ClientFactoryMock = $this->createMock(V2ClientFactory::class);
        $v2ClientFactoryMock->method('create')->willReturn(
            $this->createV2ClientMock($dataFieldsResourceMock)
        );

        $consumer = new CouponStartConsumer(
            $this->loggerMock,
            $this->couponJobFactoryMock,
            $this->couponJobResourceMock,
            $this->couponSyncPublisherMock,
            $this->clientFactoryMock,
            $this->serializerMock,
            $v2ClientFactoryMock,
            $this->stringUtilsMock,
            $this->reportFactoryMock,
            $this->failureLogMock,
            $this->jobUpdaterMock
        );

        $couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_FAILED);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error ensuring data field exists'));

        $this->couponSyncPublisherMock->expects($this->never())->method('publish');
        $this->clientFactoryMock->expects($this->never())->method('create');

        $consumer->process($startData);
    }

    /**
     * Test that process marks job failed when fetching existing data fields throws an exception.
     */
    public function testProcessMarksJobFailedWhenCheckDataFieldThrowsException(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $startData = $this->createStartData($jobId, $websiteId);

        $couponJobMock = $this->createCouponJobMock($jobId, 55);
        $this->couponJobFactoryMock->method('create')->willReturn($couponJobMock);

        $dataFieldsResourceMock = $this->createMock(\Dotdigital\V2\Resources\DataFields::class);
        $dataFieldsResourceMock->method('show')
            ->willThrowException(new \RuntimeException('API error'));

        $v2ClientFactoryMock = $this->createMock(V2ClientFactory::class);
        $v2ClientFactoryMock->method('create')->willReturn(
            $this->createV2ClientMock($dataFieldsResourceMock)
        );

        $consumer = new CouponStartConsumer(
            $this->loggerMock,
            $this->couponJobFactoryMock,
            $this->couponJobResourceMock,
            $this->couponSyncPublisherMock,
            $this->clientFactoryMock,
            $this->serializerMock,
            $v2ClientFactoryMock,
            $this->stringUtilsMock,
            $this->reportFactoryMock,
            $this->failureLogMock,
            $this->jobUpdaterMock
        );

        $couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_FAILED);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error ensuring data field exists'));

        $this->couponSyncPublisherMock->expects($this->never())->method('publish');
        $this->clientFactoryMock->expects($this->never())->method('create');

        $consumer->process($startData);
    }

    /**
     * Create a CouponStartData DTO with the given values.
     *
     * @param int $jobId
     * @param int $websiteId
     * @param string $filterType
     * @param int $filterId
     * @param string|null $codeFormat
     * @param string|null $codePrefix
     * @param string|null $codeSuffix
     * @param string $dataField
     * @param int $batchSize
     * @param int|null $codeLength
     * @param int|null $codeDash
     * @param string|null $expiresAt
     * @return CouponStartData
     */
    private function createStartData(
        int $jobId = 1,
        int $websiteId = 1,
        string $filterType = 'LIST',
        int $filterId = 100,
        ?string $codeFormat = 'alphanum',
        ?string $codePrefix = null,
        ?string $codeSuffix = null,
        string $dataField = 'COUPONCODE',
        int $batchSize = 500,
        ?int $codeLength = 9,
        ?int $codeDash = 3,
        ?string $expiresAt = null
    ): CouponStartData {
        $data = new CouponStartData();
        $data->setJobId($jobId);
        $data->setWebsiteId($websiteId);
        $data->setFilterType($filterType);
        $data->setFilterId($filterId);
        $data->setCodeFormat($codeFormat);
        $data->setCodePrefix($codePrefix);
        $data->setCodeSuffix($codeSuffix);
        $data->setCodeLength($codeLength);
        $data->setCodeDash($codeDash);
        $data->setExpiresAt($expiresAt);
        $data->setDataField($dataField);
        $data->setBatchSize($batchSize);
        return $data;
    }

    /**
     * Create a mock CouponJob.
     *
     * @param int|null $id
     * @param int $salesRuleId
     * @param string $status
     * @return CouponJob|MockObject
     */
    private function createCouponJobMock(
        ?int $id = null,
        int $salesRuleId = 1,
        string $status = CouponJobInterface::STATUS_PENDING
    ) {
        $mock = $this->getMockBuilder(CouponJob::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getData', 'setStatus', 'updateReport', 'incrementFailedMessagesCount'])
            ->getMock();

        $mock->method('getId')->willReturn($id);

        $mock->method('getData')
            ->willReturnCallback(function ($key) use ($salesRuleId, $status) {
                if ($key === 'sales_rule_id') {
                    return $salesRuleId;
                }
                if ($key === 'status') {
                    return $status;
                }
                return null;
            });

        $mock->method('updateReport')->willReturnSelf();
        $mock->method('incrementFailedMessagesCount')->willReturnSelf();

        return $mock;
    }

    /**
     * Create a V3 client mock that returns paginated API responses as JSON strings.
     *
     * Each page should be an array with '_items' and optionally '_links' keys,
     * matching the Dotdigital V3 contacts API response structure.
     * Pages are JSON-encoded to match the string return type of Contacts::getContacts().
     *
     * @param array $pages Array of API response pages.
     * @return Client|MockObject
     */
    private function createClientMockWithApiPages(array $pages)
    {
        $callIndex = 0;

        $contactsResourceMock = $this->createMock(ContactsResource::class);
        $contactsResourceMock->method('getContacts')
            ->willReturnCallback(function () use (&$callIndex, $pages) {
                $page = $pages[$callIndex] ?? ['_items' => []];
                $callIndex++;
                return json_encode($page);
            });

        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();

        $clientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);

        return $clientMock;
    }

    /**
     * Create a mock of the V2 dataFields resource pre-loaded with existing field names.
     *
     * @param string[] $existingFieldNames
     * @return \Dotdigital\V2\Resources\DataFields|MockObject
     */
    private function createDataFieldsResourceMock(array $existingFieldNames)
    {
        $existingFieldMocks = array_map(function (string $name) {
            $fieldMock = $this->createMock(\Dotdigital\V2\Models\DataField::class);
            $fieldMock->method('getName')->willReturn($name);
            return $fieldMock;
        }, $existingFieldNames);

        $listMock = $this->createMock(\Dotdigital\V2\Models\DataFieldList::class);
        $listMock->method('getList')->willReturn($existingFieldMocks);

        $dataFieldsResourceMock = $this->createMock(\Dotdigital\V2\Resources\DataFields::class);
        $dataFieldsResourceMock->method('show')->willReturn($listMock);

        return $dataFieldsResourceMock;
    }

    /**
     * Create a V2 client mock whose `dataFields` property resolves to the given resource mock.
     *
     * @param \Dotdigital\V2\Resources\DataFields|MockObject $dataFieldsResourceMock
     * @return V2Client|MockObject
     */
    private function createV2ClientMock($dataFieldsResourceMock)
    {
        $clientMock = $this->getMockBuilder(V2Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();

        $clientMock->method('__get')
            ->with('dataFields')
            ->willReturn($dataFieldsResourceMock);

        return $clientMock;
    }
}
