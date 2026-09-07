<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\FailureLogInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterface;
use Dotdigitalgroup\Email\Model\CouponJob\ReportBuilder;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory as ImporterCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ReportBuilderTest extends TestCase
{
    /**
     * @var JobUpdaterInterface|MockObject
     */
    private $jobUpdaterMock;

    /**
     * @var ImporterCollectionFactory|MockObject
     */
    private $importerCollectionFactoryMock;

    /**
     * @var ReportInterfaceFactory|MockObject
     */
    private $reportFactoryMock;

    /**
     * @var FailureLogInterface|MockObject
     */
    private $failureLogMock;

    /**
     * @var ReportBuilder
     */
    private $reportBuilder;

    protected function setUp(): void
    {
        $this->jobUpdaterMock = $this->createMock(JobUpdaterInterface::class);
        $this->importerCollectionFactoryMock = $this->createMock(ImporterCollectionFactory::class);

        $this->reportFactoryMock = $this->createMock(ReportInterfaceFactory::class);
        $this->reportFactoryMock->method('create')
            ->willReturnCallback(function (array $args = []) {
                $data = isset($args['data']) && is_array($args['data']) ? $args['data'] : [];
                return new Report($data);
            });

        $this->failureLogMock = $this->createMock(FailureLogInterface::class);

        $this->reportBuilder = new ReportBuilder(
            $this->jobUpdaterMock,
            $this->importerCollectionFactoryMock,
            $this->reportFactoryMock,
            $this->failureLogMock
        );
    }

    /**
     * When the job updater cannot find the job it returns false; build() should
     * complete without error.
     */
    public function testBuildLogsErrorAndReturnsWhenJobNotFound(): void
    {
        $this->jobUpdaterMock->method('update')->willReturn(false);

        $this->reportBuilder->build(999, 10, 5, []);
        // No exception thrown means the test passes.
    }

    /**
     * A completed final batch aggregates the imported counts and failures, and flips
     * the job to COMPLETE once every known batch has been processed.
     */
    public function testBuildAggregatesTotalsAndCompletesJob(): void
    {
        $existingJobDetails = $this->createJobDetails([
            JobDetailsInterface::REPORT => [
                ReportInterface::TOTAL_RECORDS => 100,
                ReportInterface::TOTAL_BATCHES => 3,
                ReportInterface::BATCH_RECORDS_IMPORTED => [],
            ],
        ]);

        $couponJob = $this->createMock(CouponJob::class);
        $couponJob->method('getId')->willReturn(55);
        $couponJob->method('getJobDetails')->willReturn($existingJobDetails);

        // All 3 batches resolved => job should complete.
        $this->configureBatches(3, [Importer::IMPORTED, Importer::FAILED, Importer::IMPORTED]);

        $this->jobUpdaterMock->method('update')
            ->willReturnCallback(function (int $jobId, callable $mutator) use ($couponJob) {
                $mutator($couponJob);
                return true;
            });

        $couponJob->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_COMPLETE);

        $failures = [
            [
                'email' => 'fail@example.com',
                'failure_code' => 'INVALID',
                'description' => 'Bad address',
            ],
        ];

        // Per-contact failure detail is written to the batch's imports log outside the
        // lock, keyed by importer id so a retry replaces rather than appends.
        $this->failureLogMock->expects($this->once())
            ->method('replaceBatch')
            ->with(55, FailureLogInterface::CATEGORY_IMPORTS, 10, $failures);

        $this->reportBuilder->build(55, 10, 7, $failures);

        $report = $existingJobDetails->getReport();
        $this->assertSame(100, $report->getData(ReportInterface::TOTAL_RECORDS));
        $this->assertSame(3, $report->getData(ReportInterface::TOTAL_BATCHES));
        $this->assertSame(7, $report->getData(ReportInterface::TOTAL_RECORDS_IMPORTED));
        $this->assertSame(3, $report->getData(ReportInterface::TOTAL_BATCHES_PROCESSED));
        $this->assertSame([10 => 7], $report->getData(ReportInterface::BATCH_RECORDS_IMPORTED));

        // Failure counts are authoritative and stored on the job details, keyed by importer id.
        $this->assertSame([10 => 1], $existingJobDetails->getFailedImportsByBatch());
        $this->assertSame(1, $existingJobDetails->getFailedImportsCount());
    }

    /**
     * While batches are still outstanding, the job must not be flipped to COMPLETE.
     */
    public function testBuildDoesNotCompleteWhileBatchesRemain(): void
    {
        $existingJobDetails = $this->createJobDetails([
            JobDetailsInterface::REPORT => [
                ReportInterface::TOTAL_RECORDS => 100,
                ReportInterface::TOTAL_BATCHES => 3,
                ReportInterface::BATCH_RECORDS_IMPORTED => [],
            ],
        ]);

        $couponJob = $this->createMock(CouponJob::class);
        $couponJob->method('getId')->willReturn(55);
        $couponJob->method('getJobDetails')->willReturn($existingJobDetails);
        $couponJob->method('updateReport')->willReturnSelf();

        // Only 1 of 3 batches resolved.
        $this->configureBatches(3, [Importer::IMPORTED, Importer::IMPORTING, Importer::IMPORTING]);

        $this->jobUpdaterMock->method('update')
            ->willReturnCallback(function (int $jobId, callable $mutator) use ($couponJob) {
                $mutator($couponJob);
                return true;
            });

        $couponJob->expects($this->never())->method('setStatus');

        $this->reportBuilder->build(55, 10, 4, []);
    }

    /**
     * A job already in a terminal FAILED state (fail-fast) must not be flipped to
     * COMPLETE by a late-finishing in-flight batch, even when every batch resolves.
     */
    public function testBuildDoesNotOverrideTerminalFailedStatus(): void
    {
        $existingJobDetails = $this->createJobDetails([
            JobDetailsInterface::REPORT => [
                ReportInterface::TOTAL_RECORDS => 100,
                ReportInterface::TOTAL_BATCHES => 3,
                ReportInterface::BATCH_RECORDS_IMPORTED => [],
            ],
        ]);

        $couponJob = $this->createMock(CouponJob::class);
        $couponJob->method('getId')->willReturn(55);
        $couponJob->method('getJobDetails')->willReturn($existingJobDetails);
        $couponJob->method('updateReport')->willReturnSelf();
        $couponJob->method('getData')
            ->willReturnCallback(function ($key = '') {
                return $key === 'status' ? CouponJobInterface::STATUS_FAILED : null;
            });

        // All 3 batches resolved, which would normally complete the job.
        $this->configureBatches(3, [Importer::IMPORTED, Importer::FAILED, Importer::IMPORTED]);

        $this->jobUpdaterMock->method('update')
            ->willReturnCallback(function (int $jobId, callable $mutator) use ($couponJob) {
                $mutator($couponJob);
                return true;
            });

        // Terminal status must be preserved.
        $couponJob->expects($this->never())->method('setStatus');

        $this->reportBuilder->build(55, 10, 7, []);
    }

    /**
     * A cancelled job must never be flipped to COMPLETE by a batch that resolves
     * after the cancellation.
     */
    public function testBuildDoesNotOverrideTerminalCancelledStatus(): void
    {
        $existingJobDetails = $this->createJobDetails([
            JobDetailsInterface::REPORT => [
                ReportInterface::TOTAL_RECORDS => 100,
                ReportInterface::TOTAL_BATCHES => 3,
                ReportInterface::BATCH_RECORDS_IMPORTED => [],
            ],
        ]);

        $couponJob = $this->createMock(CouponJob::class);
        $couponJob->method('getId')->willReturn(55);
        $couponJob->method('getJobDetails')->willReturn($existingJobDetails);
        $couponJob->method('updateReport')->willReturnSelf();
        $couponJob->method('getData')
            ->willReturnCallback(function ($key = '') {
                return $key === 'status' ? CouponJobInterface::STATUS_CANCELLED : null;
            });

        // Every batch has resolved, which would normally complete the job.
        $this->configureBatches(3, [Importer::IMPORTED, Importer::FAILED, Importer::FAILED]);

        $this->jobUpdaterMock->method('update')
            ->willReturnCallback(function (int $jobId, callable $mutator) use ($couponJob) {
                $mutator($couponJob);
                return true;
            });

        $couponJob->expects($this->never())->method('setStatus');

        $this->reportBuilder->build(55, 10, 7, []);
    }

    /**
     * refresh() re-derives the aggregate counters from the batches that already
     * exist, without resetting a batch slice, rewriting a failure log or touching
     * the job status.
     */
    public function testRefreshRecalculatesCountsWithoutTouchingFailures(): void
    {
        $existingJobDetails = $this->createJobDetails([
            JobDetailsInterface::REPORT => [
                ReportInterface::TOTAL_RECORDS => 1000,
                ReportInterface::TOTAL_BATCHES => 10,
                ReportInterface::TOTAL_RECORDS_IMPORTED => 0,
                ReportInterface::TOTAL_BATCHES_PROCESSED => 0,
                ReportInterface::BATCH_RECORDS_IMPORTED => [10 => 100, 11 => 150],
            ],
            JobDetailsInterface::FAILED_IMPORTS => [10 => 2],
        ]);

        $couponJob = $this->createMock(CouponJob::class);
        $couponJob->method('getId')->willReturn(55);
        $couponJob->method('getJobDetails')->willReturn($existingJobDetails);
        $couponJob->method('getData')
            ->willReturnCallback(function ($key = '') {
                return $key === 'status' ? CouponJobInterface::STATUS_CANCELLED : null;
            });

        // Four batches exist; the cancellation sweep resolved all of them.
        $this->configureBatches(4, [Importer::IMPORTED, Importer::IMPORTED, Importer::FAILED, Importer::FAILED]);

        $this->jobUpdaterMock->method('update')
            ->willReturnCallback(function (int $jobId, callable $mutator) use ($couponJob) {
                $mutator($couponJob);
                return true;
            });

        $this->failureLogMock->expects($this->never())->method('replaceBatch');
        $couponJob->expects($this->never())->method('setStatus');

        $this->reportBuilder->refresh(55);

        $report = $existingJobDetails->getReport();

        // Seeded denominators are preserved, so the grid still reports 4 of 10.
        $this->assertSame(1000, $report->getData(ReportInterface::TOTAL_RECORDS));
        $this->assertSame(10, $report->getData(ReportInterface::TOTAL_BATCHES));
        $this->assertSame(4, $report->getData(ReportInterface::TOTAL_BATCHES_PROCESSED));

        // Only the batches that genuinely imported contribute to the record count.
        $this->assertSame(250, $report->getData(ReportInterface::TOTAL_RECORDS_IMPORTED));
        $this->assertSame([10 => 100, 11 => 150], $report->getData(ReportInterface::BATCH_RECORDS_IMPORTED));

        // Failure counts are untouched.
        $this->assertSame([10 => 2], $existingJobDetails->getFailedImportsByBatch());
    }

    /**
     * Build a real JobDetails struct backed by the shared struct factories.
     *
     * @param array $data
     * @return JobDetails
     */
    private function createJobDetails(array $data): JobDetails
    {
        return new JobDetails(
            $this->reportFactoryMock,
            $data
        );
    }

    /**
     * Configure the importer collection factory to return a batch collection.
     *
     * @param int $size
     * @param int[] $statuses
     * @return void
     */
    private function configureBatches(int $size, array $statuses): void
    {
        $batches = [];
        foreach ($statuses as $status) {
            $batch = $this->createMock(Importer::class);
            $batch->method('getData')
                ->willReturnCallback(function ($key = '') use ($status) {
                    return $key === 'import_status' ? $status : null;
                });
            $batches[] = $batch;
        }

        $collection = $this->createMock(ImporterCollection::class);
        $collection->method('getBatchesByCouponJobId')->willReturnSelf();
        $collection->method('getSize')->willReturn($size);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($batches));

        $this->importerCollectionFactoryMock->method('create')->willReturn($collection);
    }
}
