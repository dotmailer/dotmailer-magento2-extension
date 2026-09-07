<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Coupon;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\FailureLogInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\Queue\Coupon\CouponSyncConsumer;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponSyncData;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponGenerator;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as SalesRuleResource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CouponSyncConsumerTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var RuleFactory|MockObject
     */
    private $ruleFactoryMock;

    /**
     * @var SalesRuleResource|MockObject
     */
    private $salesRuleResourceMock;

    /**
     * @var DotdigitalCouponGenerator|MockObject
     */
    private $couponGeneratorMock;

    /**
     * @var MegaBatchProcessor|MockObject
     */
    private $megaBatchProcessorMock;

    /**
     * @var FailureLogInterface|MockObject
     */
    private $failureLogMock;

    /**
     * @var JobUpdaterInterface|MockObject
     */
    private $jobUpdaterMock;

    /**
     * @var CouponJobResource|MockObject
     */
    private $couponJobResourceMock;

    /**
     * @var CouponJob|MockObject
     */
    private $couponJobMock;

    /**
     * Status returned by the resource-model short-circuit check.
     *
     * @var string
     */
    private $jobStatus = CouponJobInterface::STATUS_PROCESSING;

    /**
     * @var CouponSyncConsumer
     */
    private $couponSyncConsumer;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->ruleFactoryMock = $this->createMock(RuleFactory::class);
        $this->salesRuleResourceMock = $this->createMock(SalesRuleResource::class);
        $this->couponGeneratorMock = $this->createMock(DotdigitalCouponGenerator::class);
        $this->megaBatchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->failureLogMock = $this->createMock(FailureLogInterface::class);
        $this->jobUpdaterMock = $this->createMock(JobUpdaterInterface::class);
        $this->couponJobResourceMock = $this->createMock(CouponJobResource::class);

        // Default: job is processing, so the fail-fast guard lets batches through.
        $this->jobStatus = CouponJobInterface::STATUS_PROCESSING;
        $this->couponJobResourceMock->method('getStatusById')
            ->willReturnCallback(function () {
                return $this->jobStatus;
            });

        // JobUpdater executes the mutator against a shared CouponJob mock so tests
        // can assert the status transitions triggered by the consumer.
        $this->couponJobMock = $this->getMockBuilder(CouponJob::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setStatus', 'incrementFailedMessagesCount'])
            ->getMock();
        $this->couponJobMock->method('incrementFailedMessagesCount')->willReturnSelf();

        $this->jobUpdaterMock->method('update')
            ->willReturnCallback(function (int $jobId, callable $mutator) {
                $mutator($this->couponJobMock);
                return true;
            });

        $this->couponSyncConsumer = new CouponSyncConsumer(
            $this->loggerMock,
            $this->ruleFactoryMock,
            $this->salesRuleResourceMock,
            $this->couponGeneratorMock,
            $this->megaBatchProcessorMock,
            $this->failureLogMock,
            $this->jobUpdaterMock,
            $this->couponJobResourceMock
        );
    }

    /**
     * Test process logs error, marks the job failed and returns when the sales rule is not found.
     */
    public function testProcessLogsErrorWhenSalesRuleNotFound(): void
    {
        $syncData = $this->createSyncData(10, 1, 99, ['a@test.com']);

        $ruleMock = $this->createRuleMock(null);
        $this->ruleFactoryMock->method('create')->willReturn($ruleMock);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Sales rule 99 not found'));

        $this->couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_FAILED);

        $this->couponGeneratorMock->expects($this->never())->method('generateCoupon');
        $this->megaBatchProcessorMock->expects($this->never())->method('process');

        $this->couponSyncConsumer->process($syncData);
    }

    /**
     * Test process skips the batch entirely when the linked job is already failed.
     */
    public function testProcessSkipsWhenJobAlreadyFailed(): void
    {
        $this->jobStatus = CouponJobInterface::STATUS_FAILED;

        $syncData = $this->createSyncData(10, 1, 55, ['a@test.com']);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('already marked as failed'));

        $this->ruleFactoryMock->expects($this->never())->method('create');
        $this->couponGeneratorMock->expects($this->never())->method('generateCoupon');
        $this->megaBatchProcessorMock->expects($this->never())->method('process');

        $this->couponSyncConsumer->process($syncData);
    }

    /**
     * Test process skips the batch entirely when the linked job has been cancelled.
     *
     * No coupons are generated and no importer batch is created, so a cancelled job
     * never burns through the batches still sitting on the queue.
     */
    public function testProcessSkipsWhenJobCancelled(): void
    {
        $this->jobStatus = CouponJobInterface::STATUS_CANCELLED;

        $syncData = $this->createSyncData(10, 1, 55, ['a@test.com']);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('already marked as cancelled'));

        $this->ruleFactoryMock->expects($this->never())->method('create');
        $this->couponGeneratorMock->expects($this->never())->method('generateCoupon');
        $this->megaBatchProcessorMock->expects($this->never())->method('process');
        $this->couponJobMock->expects($this->never())->method('setStatus');

        $this->couponSyncConsumer->process($syncData);
    }

    /**
     * Test process skips the batch entirely when the linked job has already completed.
     */
    public function testProcessSkipsWhenJobAlreadyComplete(): void
    {
        $this->jobStatus = CouponJobInterface::STATUS_COMPLETE;

        $syncData = $this->createSyncData(10, 1, 55, ['a@test.com']);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('already marked as complete'));

        $this->megaBatchProcessorMock->expects($this->never())->method('process');

        $this->couponSyncConsumer->process($syncData);
    }

    /**
     * Test process generates coupons and sends batch.
     */
    public function testProcessGeneratesCouponsAndSendsBatch(): void
    {
        $jobId = 10;
        $websiteId = 1;
        $salesRuleId = 55;
        $emails = ['alice@test.com', 'bob@test.com'];
        $codeFormat = 'alphanum';
        $codePrefix = 'PRE';
        $codeSuffix = 'SUF';
        $dataField = 'COUPONCODE';
        $expiresAt = '2026-12-31 23:59:59';

        $syncData = $this->createSyncData(
            $jobId,
            $websiteId,
            $salesRuleId,
            $emails,
            1,
            $codeFormat,
            $codePrefix,
            $codeSuffix,
            $dataField,
            9,
            3,
            $expiresAt
        );

        $ruleMock = $this->createRuleMock($salesRuleId);
        $this->ruleFactoryMock->method('create')->willReturn($ruleMock);

        $this->couponGeneratorMock->expects($this->exactly(2))
            ->method('generateCoupon')
            ->willReturnCallback(function (
                Rule $rule,
                ?string $cf,
                ?string $cp,
                ?string $cs,
                ?string $email,
                ?int $expireDays,
                ?int $cl,
                ?int $cd,
                ?string $ea
            ) use (
                $codeFormat,
                $codePrefix,
                $codeSuffix,
                $expiresAt
            ) {
                $this->assertSame($codeFormat, $cf);
                $this->assertSame($codePrefix, $cp);
                $this->assertSame($codeSuffix, $cs);
                $this->assertNull($expireDays);
                $this->assertSame(9, $cl);
                $this->assertSame(3, $cd);
                $this->assertSame($expiresAt, $ea);
                return 'COUPON_' . $email;
            });

        $this->megaBatchProcessorMock->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (
                array $batch,
                int $wId,
                string $importType
            ) use (
                $websiteId,
                $emails
            ) {
                $this->assertSame($websiteId, $wId);
                $this->assertSame(Importer::IMPORT_TYPE_COUPON_JOB, $importType);
                $this->assertArrayHasKey('records', $batch);
                $this->assertCount(2, $batch['records']);

                foreach ($emails as $email) {
                    $this->assertArrayHasKey($email, $batch['records']);
                    $this->assertInstanceOf(SdkContact::class, $batch['records'][$email]);
                }
            });

        $this->couponSyncConsumer->process($syncData);
    }

    /**
     * Test process logs warning, marks the job failed and returns when all coupon generations fail.
     */
    public function testProcessLogsWarningWhenAllCouponsFail(): void
    {
        $jobId = 10;
        $syncData = $this->createSyncData($jobId, 1, 55, ['fail@test.com']);

        $ruleMock = $this->createRuleMock(55);
        $this->ruleFactoryMock->method('create')->willReturn($ruleMock);

        $this->couponGeneratorMock->method('generateCoupon')
            ->willThrowException(new \Exception('Generation error'));

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Failed to generate coupon'));

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('No coupons generated'));

        $this->couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_FAILED);

        $this->megaBatchProcessorMock->expects($this->never())->method('process');

        $this->couponSyncConsumer->process($syncData);
    }

    /**
     * Test process skips failed emails but processes successful ones.
     */
    public function testProcessSkipsFailedEmailsAndContinues(): void
    {
        $jobId = 10;
        $emails = ['good@test.com', 'bad@test.com'];
        $syncData = $this->createSyncData($jobId, 1, 55, $emails);

        $ruleMock = $this->createRuleMock(55);
        $this->ruleFactoryMock->method('create')->willReturn($ruleMock);

        $callIndex = 0;
        $this->couponGeneratorMock->method('generateCoupon')
            ->willReturnCallback(function () use (&$callIndex) {
                $callIndex++;
                if ($callIndex === 2) {
                    throw new \Exception('Generation error');
                }
                return 'VALID_CODE';
            });

        $this->megaBatchProcessorMock->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (array $batch) {
                $this->assertCount(1, $batch['records']);
                $this->assertArrayHasKey('good@test.com', $batch['records']);
            });

        $this->couponSyncConsumer->process($syncData);
    }

    /**
     * Test process with null code options.
     */
    public function testProcessWithNullCodeOptions(): void
    {
        $jobId = 10;
        $syncData = $this->createSyncData($jobId, 1, 55, ['a@test.com'], 1, null, null, null, 'FIELD', null, null);

        $ruleMock = $this->createRuleMock(55);
        $this->ruleFactoryMock->method('create')->willReturn($ruleMock);

        $this->couponGeneratorMock->expects($this->once())
            ->method('generateCoupon')
            ->with($ruleMock, null, null, null, 'a@test.com', null, null, null, null)
            ->willReturn('CODE123');

        $this->megaBatchProcessorMock->expects($this->once())->method('process');

        $this->couponSyncConsumer->process($syncData);
    }

    /**
     * Create a CouponSyncData DTO.
     *
     * @param int $jobId
     * @param int $websiteId
     * @param int $salesRuleId
     * @param array $emails
     * @param int $batchNumber
     * @param string|null $codeFormat
     * @param string|null $codePrefix
     * @param string|null $codeSuffix
     * @param string $dataField
     * @param int|null $codeLength
     * @param int|null $codeDash
     * @param string|null $expiresAt
     * @return CouponSyncData
     */
    private function createSyncData(
        int $jobId = 1,
        int $websiteId = 1,
        int $salesRuleId = 1,
        array $emails = [],
        int $batchNumber = 1,
        ?string $codeFormat = 'alphanum',
        ?string $codePrefix = null,
        ?string $codeSuffix = null,
        string $dataField = 'COUPONCODE',
        ?int $codeLength = 9,
        ?int $codeDash = 3,
        ?string $expiresAt = null
    ): CouponSyncData {
        $data = new CouponSyncData();
        $data->setJobId($jobId);
        $data->setWebsiteId($websiteId);
        $data->setSalesRuleId($salesRuleId);
        $data->setEmails($emails);
        $data->setBatchNumber($batchNumber);
        $data->setCodeFormat($codeFormat);
        $data->setCodePrefix($codePrefix);
        $data->setCodeSuffix($codeSuffix);
        $data->setCodeLength($codeLength);
        $data->setCodeDash($codeDash);
        $data->setExpiresAt($expiresAt);
        $data->setDataField($dataField);
        return $data;
    }

    /**
     * Create a mock Rule.
     *
     * @param int|null $id
     * @return Rule|MockObject
     */
    private function createRuleMock(?int $id): MockObject
    {
        $mock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();

        $mock->method('getId')->willReturn($id);

        return $mock;
    }
}
