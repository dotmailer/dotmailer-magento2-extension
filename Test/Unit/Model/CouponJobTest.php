<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetails\ReportInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterfaceFactory;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobConfiguration;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails\Report;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use InvalidArgumentException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CouponJobTest extends TestCase
{
    /** @var CouponJob */
    private $model;

    /** @var SchemaValidatorFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $schemaValidatorFactory;

    /** @var JobDetailsInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $jobDetailsFactory;

    /** @var JobConfigurationInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $jobConfigurationFactory;

    /** @var ReportInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $reportFactory;

    protected function setUp(): void
    {
        $eventManager = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $context = $this->createMock(Context::class);
        $context->method('getEventDispatcher')->willReturn($eventManager);

        $registry = $this->createMock(Registry::class);
        $resource = $this->createMock(CouponJobResource::class);
        $resource->method('getIdFieldName')->willReturn('id');
        $this->schemaValidatorFactory = $this->createMock(SchemaValidatorFactory::class);
        $this->jobDetailsFactory = $this->createMock(JobDetailsInterfaceFactory::class);
        $this->jobConfigurationFactory = $this->createMock(JobConfigurationInterfaceFactory::class);
        $serializer = $this->createMock(SerializerInterface::class);

        $this->reportFactory = $this->createMock(ReportInterfaceFactory::class);
        $this->reportFactory->method('create')->willReturnCallback(function (array $args = []) {
            $data = isset($args['data']) && is_array($args['data']) ? $args['data'] : [];
            return new Report($data);
        });

        // Default JobDetails factory behaviour: create an empty JobDetails struct
        $this->jobDetailsFactory->method('create')->willReturnCallback(function (array $args = []) {
            $data = isset($args['data']) && is_array($args['data']) ? $args['data'] : [];
            return new JobDetails(
                $this->reportFactory,
                $data
            );
        });

        $this->model = new CouponJob(
            $context,
            $registry,
            $this->schemaValidatorFactory,
            $this->jobDetailsFactory,
            $this->jobConfigurationFactory,
            $serializer,
            [],
            $resource
        );
    }

    private function mockValidator(bool $willBeValid, array $errors = []): void
    {
        $validator = $this->getMockBuilder('Dotdigitalgroup\\Email\\Model\\Validator\\Schema\\SchemaValidator')
            ->disableOriginalConstructor()
            ->getMock();
        $validator->method('setPattern')->willReturn(null);
        $validator->method('isValid')->willReturn($willBeValid);
        $validator->method('getErrors')->willReturn($errors);
        $this->schemaValidatorFactory->method('create')->willReturn($validator);
    }

    public function testGetValidStatuses()
    {
        $this->assertSame([
            CouponJobInterface::STATUS_PENDING,
            CouponJobInterface::STATUS_PROCESSING,
            CouponJobInterface::STATUS_COMPLETE,
            CouponJobInterface::STATUS_FAILED,
            CouponJobInterface::STATUS_CANCELLED,
        ], $this->model->getValidStatuses());
    }

    public function testSetStatusAcceptsValidStatuses()
    {
        $this->model->setStatus(CouponJobInterface::STATUS_PROCESSING);
        $this->assertSame(CouponJobInterface::STATUS_PROCESSING, $this->model->getData('status'));

        $this->model->setStatus(CouponJobInterface::STATUS_COMPLETE);
        $this->assertSame(CouponJobInterface::STATUS_COMPLETE, $this->model->getData('status'));
    }

    public function testSetStatusRejectsInvalidStatus()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->model->setStatus('invalid_status');
    }

    public function testGetJobDetailsCreatesDefaultWhenAbsent()
    {
        $jobDetails = $this->model->getJobDetails();
        $this->assertInstanceOf(JobDetailsInterface::class, $jobDetails);
        // Ensure same instance is cached on the model
        $this->assertSame($jobDetails, $this->model->getData('job_details'));
    }

    public function testSetAndGetJobDetailsValidatesAndPersists()
    {
        $this->mockValidator(true);

        $jobDetails = new JobDetails(
            $this->reportFactory
        );
        $report = new Report([
            ReportInterface::TOTAL_RECORDS => 0,
            ReportInterface::TOTAL_RECORDS_IMPORTED => 0,
            ReportInterface::TOTAL_BATCHES => 0,
            ReportInterface::TOTAL_BATCHES_PROCESSED => 0,
        ]);
        $jobDetails->setReport($report);

        $this->model->setJobDetails($jobDetails);

        $this->assertSame($jobDetails, $this->model->getData('job_details'));
        $this->assertSame(0, $this->model->getJobDetails()->getReport()->getData(ReportInterface::TOTAL_BATCHES));
    }

    public function testSetJobDetailsThrowsOnInvalidData()
    {
        $this->mockValidator(false, ['some.error' => 'Bad data']);

        $jobDetails = new JobDetails(
            $this->reportFactory
        );

        $this->expectException(InvalidArgumentException::class);
        $this->model->setJobDetails($jobDetails);
    }

    public function testSetAndGetJobConfigurationValidatesAndPersists()
    {
        $this->mockValidator(true);

        $configuration = new JobConfiguration([
            JobConfigurationInterface::BATCH_SIZE => 100,
            JobConfigurationInterface::FILTER_TYPE => 'LIST',
            JobConfigurationInterface::FILTER_ID => 1,
            JobConfigurationInterface::CODE_FORMAT => 'alnum',
            JobConfigurationInterface::CODE_LENGTH => 12,
            JobConfigurationInterface::CODE_DASH => 3,
            JobConfigurationInterface::CODE_PREFIX => 'PFX',
            JobConfigurationInterface::CODE_SUFFIX => 'SFX',
            JobConfigurationInterface::DATA_FIELD => 'field'
        ]);

        $this->model->setJobConfiguration($configuration);
        $this->assertSame($configuration, $this->model->getData('job_configuration'));
        $this->assertSame($configuration, $this->model->getJobConfiguration());
    }

    public function testSetJobConfigurationThrowsOnInvalid()
    {
        $this->mockValidator(false, ['config.error' => 'Invalid config']);

        $configuration = new JobConfiguration([
            JobConfigurationInterface::BATCH_SIZE => 0
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->model->setJobConfiguration($configuration);
    }

    public function testUpdateReportReplacesReport()
    {
        $this->mockValidator(true);

        $report = new Report([
            ReportInterface::TOTAL_RECORDS => 10,
            ReportInterface::TOTAL_RECORDS_IMPORTED => 2,
            ReportInterface::TOTAL_BATCHES => 5,
            ReportInterface::TOTAL_BATCHES_PROCESSED => 1,
        ]);

        $this->model->updateReport($report);
        $stored = $this->model->getJobDetails()->getReport();
        $this->assertSame(10, $stored->getData(ReportInterface::TOTAL_RECORDS));
        $this->assertSame(1, $stored->getData(ReportInterface::TOTAL_BATCHES_PROCESSED));
    }

    public function testIncrementBatchesProcessedIncrementsCounter()
    {
        $this->mockValidator(true);

        // Seed report with zero processed
        $seedReport = new Report([
            ReportInterface::TOTAL_RECORDS => 0,
            ReportInterface::TOTAL_RECORDS_IMPORTED => 0,
            ReportInterface::TOTAL_BATCHES => 0,
            ReportInterface::TOTAL_BATCHES_PROCESSED => 0,
        ]);

        $this->model->updateReport($seedReport);
        $this->model->incrementBatchesProcessed();
        $this->model->incrementBatchesProcessed(2);

        $stored = $this->model->getJobDetails()->getReport();
        $this->assertSame(3, $stored->getData(ReportInterface::TOTAL_BATCHES_PROCESSED));
    }

    public function testIncrementFailedMessagesCountIncrementsCounter()
    {
        $this->mockValidator(true);

        $this->model->incrementFailedMessagesCount();
        $this->model->incrementFailedMessagesCount(2);

        $this->assertSame(3, $this->model->getJobDetails()->getFailedMessagesCount());
    }

    public function testBeforeSaveSetsTimestamps()
    {
        $this->mockValidator(true);
        $this->model->setId(null);
        $this->model->beforeSave();
        $this->assertNotEmpty($this->model->getData('updated_at'));
        $this->assertNotEmpty($this->model->getData('created_at'));
    }
}
