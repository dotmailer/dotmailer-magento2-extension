<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Coupon;

use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobConfigurationInterface;
use Dotdigitalgroup\Email\Model\CouponJob\Struct\JobConfiguration;
use Dotdigitalgroup\Email\Model\Queue\Coupon\CouponStartPublisher;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponStartData;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponStartDataFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CouponStartPublisherTest extends TestCase
{
    /**
     * @var PublisherInterface|MockObject
     */
    private $publisherMock;

    /**
     * @var CouponStartDataFactory|MockObject
     */
    private $couponStartDataFactoryMock;

    /**
     * @var CouponStartPublisher
     */
    private $couponStartPublisher;

    protected function setUp(): void
    {
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->couponStartDataFactoryMock = $this->createMock(CouponStartDataFactory::class);

        $this->couponStartPublisher = new CouponStartPublisher(
            $this->publisherMock,
            $this->couponStartDataFactoryMock
        );
    }

    /**
     * Test that publish creates a CouponStartData DTO from a CouponJob model and publishes it.
     */
    public function testPublishCreatesDataAndPublishesMessage(): void
    {
        $jobId = 42;
        $websiteId = 1;
        $filterType = 'LIST';
        $filterId = 100;
        $codeFormat = 'alphanum';
        $codeLength = 12;
        $codeDash = 4;
        $codePrefix = 'PRE';
        $codeSuffix = 'SUF';
        $dataField = 'COUPONCODE';
        $batchSize = 500;
        $expiresAt = '2026-12-31 23:59:59';

        $jobConfigMock = $this->getMockBuilder(JobConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $configMap = [
            [JobConfigurationInterface::FILTER_TYPE, null, $filterType],
            [JobConfigurationInterface::FILTER_ID, null, $filterId],
            [JobConfigurationInterface::CODE_FORMAT, null, $codeFormat],
            [JobConfigurationInterface::CODE_LENGTH, null, $codeLength],
            [JobConfigurationInterface::CODE_DASH, null, $codeDash],
            [JobConfigurationInterface::CODE_PREFIX, null, $codePrefix],
            [JobConfigurationInterface::CODE_SUFFIX, null, $codeSuffix],
            [JobConfigurationInterface::DATA_FIELD, null, $dataField],
            [JobConfigurationInterface::EXPIRES_AT, null, $expiresAt],
            [JobConfigurationInterface::BATCH_SIZE, null, $batchSize],
        ];

        $jobConfigMock->method('getData')
            ->willReturnMap($configMap);

        $couponJobMock = $this->getMockBuilder(CouponJob::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getJobConfiguration', 'getData'])
            ->getMock();

        $couponJobMock->method('getId')
            ->willReturn($jobId);

        $couponJobMock->method('getJobConfiguration')
            ->willReturn($jobConfigMock);

        $couponJobMock->method('getData')
            ->with('website_id')
            ->willReturn($websiteId);

        $dataMock = $this->createMock(CouponStartData::class);

        $this->couponStartDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($dataMock);

        $dataMock->expects($this->once())->method('setJobId')->with($jobId);
        $dataMock->expects($this->once())->method('setWebsiteId')->with($websiteId);
        $dataMock->expects($this->once())->method('setFilterType')->with($filterType);
        $dataMock->expects($this->once())->method('setFilterId')->with($filterId);
        $dataMock->expects($this->once())->method('setCodeFormat')->with($codeFormat);
        $dataMock->expects($this->once())->method('setCodeLength')->with($codeLength);
        $dataMock->expects($this->once())->method('setCodeDash')->with($codeDash);
        $dataMock->expects($this->once())->method('setCodePrefix')->with($codePrefix);
        $dataMock->expects($this->once())->method('setCodeSuffix')->with($codeSuffix);
        $dataMock->expects($this->once())->method('setDataField')->with($dataField);
        $dataMock->expects($this->once())->method('setExpiresAt')->with($expiresAt);
        $dataMock->expects($this->once())->method('setBatchSize')->with($batchSize);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(CouponStartPublisher::TOPIC_COUPON_START, $dataMock);

        $this->couponStartPublisher->publish($couponJobMock);
    }

    /**
     * Test that the topic constant has the expected value.
     */
    public function testTopicConstant(): void
    {
        $this->assertSame('ddg.coupon.start', CouponStartPublisher::TOPIC_COUPON_START);
    }
}
