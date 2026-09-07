<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\CouponJob\Struct\JobDetails;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ReportTest extends TestCase
{
    /**
     * @var Report
     */
    private $model;

    protected function setUp(): void
    {
        $this->model = new Report();
    }

    public function testGetValidationPatternReturnsPatternFromInterface()
    {
        $this->assertSame(ReportInterface::VALIDATION_PATTERN, $this->model->getValidationPattern());
    }

    public function testDataCanBeSetAndRetrieved()
    {
        $data = [
            ReportInterface::TOTAL_RECORDS => 100,
            ReportInterface::TOTAL_RECORDS_IMPORTED => 80,
            ReportInterface::TOTAL_BATCHES => 10,
            ReportInterface::TOTAL_BATCHES_PROCESSED => 8,
        ];

        $this->model->setData($data);

        $this->assertSame(100, $this->model->getData(ReportInterface::TOTAL_RECORDS));
        $this->assertSame(80, $this->model->getData(ReportInterface::TOTAL_RECORDS_IMPORTED));
        $this->assertSame(10, $this->model->getData(ReportInterface::TOTAL_BATCHES));
        $this->assertSame(8, $this->model->getData(ReportInterface::TOTAL_BATCHES_PROCESSED));
    }
}
