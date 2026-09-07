<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\CouponJob;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobCancellerInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobUpdaterInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\ReportBuilderInterface;
use Dotdigitalgroup\Email\Model\CouponJob\JobCanceller;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory as ImporterCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class JobCancellerTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var CouponJobResource|MockObject
     */
    private $couponJobResourceMock;

    /**
     * @var JobUpdaterInterface|MockObject
     */
    private $jobUpdaterMock;

    /**
     * @var ImporterCollectionFactory|MockObject
     */
    private $importerCollectionFactoryMock;

    /**
     * @var ReportBuilderInterface|MockObject
     */
    private $reportBuilderMock;

    /**
     * @var CouponJob|MockObject
     */
    private $couponJobMock;

    /**
     * Status reported by the resource model, and by the job inside the lock.
     *
     * @var string|null
     */
    private $jobStatus = CouponJobInterface::STATUS_PROCESSING;

    /**
     * @var JobCanceller
     */
    private $jobCanceller;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->couponJobResourceMock = $this->createMock(CouponJobResource::class);
        $this->jobUpdaterMock = $this->createMock(JobUpdaterInterface::class);
        $this->importerCollectionFactoryMock = $this->createMock(ImporterCollectionFactory::class);
        $this->reportBuilderMock = $this->createMock(ReportBuilderInterface::class);

        $this->couponJobResourceMock->method('getStatusById')
            ->willReturnCallback(function () {
                return $this->jobStatus;
            });

        $this->couponJobMock = $this->getMockBuilder(CouponJob::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setStatus'])
            ->getMock();

        $this->couponJobMock->method('getData')
            ->willReturnCallback(function ($key = '') {
                return $key === 'status' ? $this->jobStatus : null;
            });

        // JobUpdater runs the mutator under a row lock and swallows any throwable,
        // returning false when the mutation could not be applied.
        $this->jobUpdaterMock->method('update')
            ->willReturnCallback(function (int $jobId, callable $mutator) {
                try {
                    $mutator($this->couponJobMock);
                } catch (\Throwable $e) {
                    return false;
                }
                return true;
            });

        $this->jobCanceller = new JobCanceller(
            $this->loggerMock,
            $this->couponJobResourceMock,
            $this->jobUpdaterMock,
            $this->importerCollectionFactoryMock,
            $this->reportBuilderMock
        );
    }

    /**
     * A processing job is cancelled, its never-sent batches are failed with the
     * shared message, and the report is reconciled.
     *
     * Batches already accepted by Dotdigital are not swept here — they keep polling
     * and fold their real numbers in via ReportBuilder::build() as they finish.
     */
    public function testCancelWritesStatusSweepsBatchesThenRefreshesReport(): void
    {
        $this->jobStatus = CouponJobInterface::STATUS_PROCESSING;
        $this->configureBatchCount(4);

        $sequence = [];

        $this->couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_CANCELLED)
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'status';
                return $this->couponJobMock;
            });

        $this->couponJobResourceMock->expects($this->once())
            ->method('failUnsentBatches')
            ->with(55, JobCancellerInterface::CANCELLED_MESSAGE)
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'sweep';
                return 2;
            });

        $this->reportBuilderMock->expects($this->once())
            ->method('refresh')
            ->with(55)
            ->willReturnCallback(function () use (&$sequence) {
                $sequence[] = 'refresh';
            });

        $this->assertTrue($this->jobCanceller->cancel(55));

        // The status must land before the sweep, so a racing consumer sees the
        // cancellation rather than creating fresh batches behind it.
        $this->assertSame(['status', 'sweep', 'refresh'], $sequence);
    }

    /**
     * A pending job that never produced a batch is cancelled without a report refresh.
     */
    public function testCancelSkipsReportRefreshWhenNoBatchesExist(): void
    {
        $this->jobStatus = CouponJobInterface::STATUS_PENDING;
        $this->configureBatchCount(0);

        $this->couponJobMock->expects($this->once())
            ->method('setStatus')
            ->with(CouponJobInterface::STATUS_CANCELLED);

        $this->couponJobResourceMock->expects($this->once())
            ->method('failUnsentBatches')
            ->willReturn(0);

        $this->reportBuilderMock->expects($this->never())->method('refresh');

        $this->assertTrue($this->jobCanceller->cancel(55));
    }

    /**
     * A job that has already reached a terminal state is left completely alone.
     *
     * @param string $status
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('terminalStatusProvider')]
    public function testCancelRejectsTerminalStatuses(string $status): void
    {
        $this->jobStatus = $status;

        $this->jobUpdaterMock->expects($this->never())->method('update');
        $this->couponJobResourceMock->expects($this->never())->method('failUnsentBatches');
        $this->reportBuilderMock->expects($this->never())->method('refresh');

        $this->assertFalse($this->jobCanceller->cancel(55));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function terminalStatusProvider(): array
    {
        return [
            'complete' => [CouponJobInterface::STATUS_COMPLETE],
            'failed' => [CouponJobInterface::STATUS_FAILED],
            'cancelled' => [CouponJobInterface::STATUS_CANCELLED],
        ];
    }

    /**
     * A job that no longer exists cannot be cancelled.
     */
    public function testCancelRejectsMissingJob(): void
    {
        $this->jobStatus = null;

        $this->jobUpdaterMock->expects($this->never())->method('update');
        $this->couponJobResourceMock->expects($this->never())->method('failUnsentBatches');

        $this->assertFalse($this->jobCanceller->cancel(55));
    }

    /**
     * When the locked write fails — for example the job raced to a terminal state
     * between the pre-check and the lock — nothing else is touched.
     */
    public function testCancelDoesNotSweepWhenStatusWriteFails(): void
    {
        $this->jobStatus = CouponJobInterface::STATUS_PROCESSING;

        $jobUpdaterMock = $this->createMock(JobUpdaterInterface::class);
        $jobUpdaterMock->method('update')->willReturn(false);

        $jobCanceller = new JobCanceller(
            $this->loggerMock,
            $this->couponJobResourceMock,
            $jobUpdaterMock,
            $this->importerCollectionFactoryMock,
            $this->reportBuilderMock
        );

        $this->couponJobResourceMock->expects($this->never())->method('failUnsentBatches');
        $this->reportBuilderMock->expects($this->never())->method('refresh');

        $this->assertFalse($jobCanceller->cancel(55));
    }

    /**
     * Configure the importer collection factory to report a batch count.
     *
     * @param int $size
     * @return void
     */
    private function configureBatchCount(int $size): void
    {
        $collection = $this->createMock(ImporterCollection::class);
        $collection->method('getBatchesByCouponJobId')->willReturnSelf();
        $collection->method('getSize')->willReturn($size);

        $this->importerCollectionFactoryMock->method('create')->willReturn($collection);
    }
}
