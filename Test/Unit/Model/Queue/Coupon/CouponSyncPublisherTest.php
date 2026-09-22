<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Coupon;

use Dotdigitalgroup\Email\Model\Queue\Coupon\CouponSyncPublisher;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponSyncData;
use Dotdigitalgroup\Email\Model\Queue\Data\CouponSyncDataFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CouponSyncPublisherTest extends TestCase
{
    /**
     * @var PublisherInterface|MockObject
     */
    private $publisherMock;

    /**
     * @var CouponSyncDataFactory|MockObject
     */
    private $couponSyncDataFactoryMock;

    /**
     * @var CouponSyncPublisher
     */
    private $couponSyncPublisher;

    protected function setUp(): void
    {
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->couponSyncDataFactoryMock = $this->createMock(CouponSyncDataFactory::class);

        $this->couponSyncPublisher = new CouponSyncPublisher(
            $this->publisherMock,
            $this->couponSyncDataFactoryMock
        );
    }

    /**
     * Test that publish creates a CouponSyncData DTO and publishes it.
     */
    public function testPublishCreatesDataAndPublishesMessage(): void
    {
        $jobId = 10;
        $websiteId = 1;
        $salesRuleId = 55;
        $emails = ['alice@example.com', 'bob@example.com'];
        $batchNumber = 3;
        $codeFormat = 'alphanum';
        $codePrefix = 'PRE';
        $codeSuffix = 'SUF';
        $codeLength = 12;
        $codeDash = 4;
        $expiresAt = '2026-12-31 23:59:59';
        $dataField = 'COUPONCODE';

        $dataMock = $this->createMock(CouponSyncData::class);

        $this->couponSyncDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($dataMock);

        $dataMock->expects($this->once())->method('setJobId')->with($jobId);
        $dataMock->expects($this->once())->method('setWebsiteId')->with($websiteId);
        $dataMock->expects($this->once())->method('setSalesRuleId')->with($salesRuleId);
        $dataMock->expects($this->once())->method('setEmails')->with($emails);
        $dataMock->expects($this->once())->method('setBatchNumber')->with($batchNumber);
        $dataMock->expects($this->once())->method('setCodeFormat')->with($codeFormat);
        $dataMock->expects($this->once())->method('setCodePrefix')->with($codePrefix);
        $dataMock->expects($this->once())->method('setCodeSuffix')->with($codeSuffix);
        $dataMock->expects($this->once())->method('setCodeLength')->with($codeLength);
        $dataMock->expects($this->once())->method('setCodeDash')->with($codeDash);
        $dataMock->expects($this->once())->method('setExpiresAt')->with($expiresAt);
        $dataMock->expects($this->once())->method('setDataField')->with($dataField);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(CouponSyncPublisher::TOPIC_COUPON_SYNC, $dataMock);

        $this->couponSyncPublisher->publish(
            $jobId,
            $websiteId,
            $salesRuleId,
            $emails,
            $batchNumber,
            $codeFormat,
            $codePrefix,
            $codeSuffix,
            $codeLength,
            $codeDash,
            $expiresAt,
            $dataField
        );
    }

    /**
     * Test publish with nullable code format/prefix/suffix.
     */
    public function testPublishWithNullCodeOptions(): void
    {
        $jobId = 5;
        $websiteId = 2;
        $salesRuleId = 30;
        $emails = ['test@example.com'];
        $batchNumber = 1;
        $dataField = 'VOUCHER';

        $dataMock = $this->createMock(CouponSyncData::class);

        $this->couponSyncDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($dataMock);

        $dataMock->expects($this->once())->method('setJobId')->with($jobId);
        $dataMock->expects($this->once())->method('setWebsiteId')->with($websiteId);
        $dataMock->expects($this->once())->method('setSalesRuleId')->with($salesRuleId);
        $dataMock->expects($this->once())->method('setEmails')->with($emails);
        $dataMock->expects($this->once())->method('setBatchNumber')->with($batchNumber);
        $dataMock->expects($this->once())->method('setCodeFormat')->with(null);
        $dataMock->expects($this->once())->method('setCodePrefix')->with(null);
        $dataMock->expects($this->once())->method('setCodeSuffix')->with(null);
        $dataMock->expects($this->once())->method('setCodeLength')->with(null);
        $dataMock->expects($this->once())->method('setCodeDash')->with(null);
        $dataMock->expects($this->once())->method('setExpiresAt')->with(null);
        $dataMock->expects($this->once())->method('setDataField')->with($dataField);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(CouponSyncPublisher::TOPIC_COUPON_SYNC, $dataMock);

        $this->couponSyncPublisher->publish(
            $jobId,
            $websiteId,
            $salesRuleId,
            $emails,
            $batchNumber,
            null,
            null,
            null,
            null,
            null,
            null,
            $dataField
        );
    }

    /**
     * Test that the topic constant has the expected value.
     */
    public function testTopicConstant(): void
    {
        $this->assertSame('ddg.coupon.sync', CouponSyncPublisher::TOPIC_COUPON_SYNC);
    }
}
