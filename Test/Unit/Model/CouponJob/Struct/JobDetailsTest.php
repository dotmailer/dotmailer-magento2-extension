<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\CouponJob\Struct;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class JobDetailsTest extends TestCase
{
    /**
     * @var JobDetails
     */
    private $model;

    /**
     * @var ReportInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $reportFactoryMock;

    protected function setUp(): void
    {
        $this->reportFactoryMock = $this->createMock(ReportInterfaceFactory::class);

        $this->model = new JobDetails(
            $this->reportFactoryMock
        );
    }

    public function testGetValidationPatternReturnsPatternFromInterface()
    {
        $this->assertSame(JobDetailsInterface::VALIDATION_PATTERN, $this->model->getValidationPattern());
    }

    public function testGetReportCallsFactory()
    {
        $reportData = ['total_records' => 10];
        $this->model->setData(JobDetailsInterface::REPORT, $reportData);

        $reportMock = $this->createMock(ReportInterface::class);
        $this->reportFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => $reportData])
            ->willReturn($reportMock);

        $this->assertSame($reportMock, $this->model->getReport());
    }

    public function testSetReportPersistsData()
    {
        $reportData = ['total_records' => 10];
        $reportMock = $this->createMock(Report::class);
        $reportMock->method('getData')->willReturn($reportData);

        $this->model->setReport($reportMock);

        $this->assertSame($reportData, $this->model->getData(JobDetailsInterface::REPORT));
    }

    public function testFailedImportsCountIsStoredPerBatchAndSummed()
    {
        $this->model->setFailedImportsCount(10, 3);
        $this->model->setFailedImportsCount(20, 2);

        $this->assertSame([10 => 3, 20 => 2], $this->model->getFailedImportsByBatch());
        $this->assertSame(5, $this->model->getFailedImportsCount());
    }

    public function testSetFailedImportsCountOverridesOnRetry()
    {
        $this->model->setFailedImportsCount(10, 3);
        // A retry of the same batch replaces rather than accumulates.
        $this->model->setFailedImportsCount(10, 1);

        $this->assertSame([10 => 1], $this->model->getFailedImportsByBatch());
        $this->assertSame(1, $this->model->getFailedImportsCount());
    }

    public function testSetFailedImportsCountZeroClearsBatch()
    {
        $this->model->setFailedImportsCount(10, 3);
        $this->model->setFailedImportsCount(10, 0);

        $this->assertSame([], $this->model->getFailedImportsByBatch());
        $this->assertSame(0, $this->model->getFailedImportsCount());
    }

    public function testFailedMessagesCountIsStoredAndRetrieved()
    {
        $this->assertSame(0, $this->model->getFailedMessagesCount());

        $this->model->setFailedMessagesCount(4);

        $this->assertSame(4, $this->model->getFailedMessagesCount());
    }
}
