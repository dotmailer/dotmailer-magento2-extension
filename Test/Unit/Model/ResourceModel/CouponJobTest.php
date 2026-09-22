<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobDetailsInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobConfiguration;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobDetails;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class CouponJobTest extends TestCase
{
    /**
     * @var CouponJob
     */
    private $resourceModel;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->serializerMock = $this->createMock(Json::class);

        // AbstractDb::__construct dependencies
        $resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $resourceConnectionMock->method('getTableName')->willReturnArgument(0);
        $this->contextMock->method('getResources')->willReturn($resourceConnectionMock);
        $this->contextMock->method('getTransactionManager')
            ->willReturn($this->createMock(TransactionManagerInterface::class));
        $this->contextMock->method('getObjectRelationProcessor')
            ->willReturn($this->createMock(ObjectRelationProcessor::class));

        $this->resourceModel = new CouponJob(
            $this->contextMock,
            $this->serializerMock
        );
    }

    public function testConstructInitializesTableAndId()
    {
        $this->assertEquals(SchemaInterface::EMAIL_COUPON_JOB_TABLE, $this->resourceModel->getMainTable());
        $this->assertEquals('id', $this->resourceModel->getIdFieldName());
    }

    public function testHydrateJobConfiguration()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $jobConfigJson = '{"batch_size": 100}';
        $jobConfigData = ['batch_size' => 100];

        $objectMock->expects($this->atLeastOnce())
            ->method('getData')
            ->with('job_configuration')
            ->willReturn($jobConfigJson);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($jobConfigJson)
            ->willReturn($jobConfigData);

        $objectMock->expects($this->once())
            ->method('setData')
            ->with('job_configuration', $jobConfigData)
            ->willReturnSelf();

        $this->resourceModel->hydrateJobConfiguration($objectMock);
    }

    public function testHydrateJobConfigurationWithInvalidJson()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $invalidJson = 'invalid_json';

        $objectMock->expects($this->atLeastOnce())
            ->method('getData')
            ->with('job_configuration')
            ->willReturn($invalidJson);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($invalidJson)
            ->willThrowException(new Exception('Unserialize error'));

        $objectMock->expects($this->once())
            ->method('setData')
            ->with('job_configuration', [])
            ->willReturnSelf();

        $this->resourceModel->hydrateJobConfiguration($objectMock);
    }

    public function testHydrateJobDetails()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $jobDetailsJson = '{"report": {}}';
        $jobDetailsData = ['report' => []];

        $objectMock->expects($this->atLeastOnce())
            ->method('getData')
            ->with('job_details')
            ->willReturn($jobDetailsJson);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($jobDetailsJson)
            ->willReturn($jobDetailsData);

        $objectMock->expects($this->once())
            ->method('setData')
            ->with('job_details', $jobDetailsData)
            ->willReturnSelf();

        $this->resourceModel->hydrateJobDetails($objectMock);
    }

    public function testDehydrateJobDetails()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $jobDetailsMock = $this->getMockBuilder(JobDetails::class)
            ->disableOriginalConstructor()
            ->getMock();

        $jobDetailsJson = '{"report": {}}';

        $objectMock->expects($this->once())
            ->method('getData')
            ->with('job_details')
            ->willReturn($jobDetailsMock);

        $jobDetailsMock->expects($this->once())
            ->method('toJson')
            ->willReturn($jobDetailsJson);

        $objectMock->expects($this->once())
            ->method('setData')
            ->with('job_details', $jobDetailsJson)
            ->willReturnSelf();

        $this->resourceModel->dehydrateJobDetails($objectMock);
    }

    public function testBeforeSaveCallsDehydrateMethods()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $jobConfigMock = $this->getMockBuilder(JobConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $jobDetailsMock = $this->getMockBuilder(JobDetails::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectMock->method('getData')
            ->willReturnMap([
                ['job_configuration', null, $jobConfigMock],
                ['job_details', null, $jobDetailsMock]
            ]);

        $jobConfigMock->expects($this->once())->method('toJson')->willReturn('{"config":true}');
        $jobDetailsMock->expects($this->once())->method('toJson')->willReturn('{"details":true}');

        $objectMock->expects($this->exactly(2))
            ->method('setData');

        // Accessing protected method _beforeSave
        $reflection = new ReflectionClass(CouponJob::class);
        $method = $reflection->getMethod('_beforeSave');
        $method->invokeArgs($this->resourceModel, [$objectMock]);
    }

    public function testAfterLoadCallsHydrateMethods()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['job_details', null, '{}'],
                ['job_configuration', null, '{}']
            ]);

        $this->serializerMock->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn([]);

        $objectMock->expects($this->exactly(2))
            ->method('setData');

        // Accessing protected method _afterLoad
        $reflection = new ReflectionClass(CouponJob::class);
        $method = $reflection->getMethod('_afterLoad');
        $method->invokeArgs($this->resourceModel, [$objectMock]);
    }

    public function testAfterSaveCallsHydrateMethods()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['job_details', null, '{}'],
                ['job_configuration', null, '{}']
            ]);

        $this->serializerMock->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn([]);

        $objectMock->expects($this->exactly(2))
            ->method('setData');

        // Accessing protected method _afterSave
        $reflection = new ReflectionClass(CouponJob::class);
        $method = $reflection->getMethod('_afterSave');
        $method->invokeArgs($this->resourceModel, [$objectMock]);
    }

    public function testHydrateJobConfigurationWithNonString()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectMock->expects($this->once())
            ->method('getData')
            ->with('job_configuration')
            ->willReturn(['already_array']);

        $this->serializerMock->expects($this->never())
            ->method('unserialize');

        $this->resourceModel->hydrateJobConfiguration($objectMock);
    }

    public function testHydrateJobDetailsWithNonString()
    {
        $objectMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectMock->expects($this->once())
            ->method('getData')
            ->with('job_details')
            ->willReturn(['already_array']);

        $this->serializerMock->expects($this->never())
            ->method('unserialize');

        $this->resourceModel->hydrateJobDetails($objectMock);
    }
}
