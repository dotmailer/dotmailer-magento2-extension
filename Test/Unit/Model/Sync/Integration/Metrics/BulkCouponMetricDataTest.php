<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Integration\Metrics;

use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Model\Sync\Integration\Metrics\BulkCouponMetricData;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BulkCouponMetricDataTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var Select|MockObject
     */
    private $selectMock;

    /**
     * @var BulkCouponMetricData
     */
    private $subject;

    protected function setUp(): void
    {
        $this->selectMock = $this->createMock(Select::class);
        $this->selectMock->method('from')->willReturnSelf();
        $this->selectMock->method('where')->willReturnSelf();

        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->connectionMock->method('select')->willReturn($this->selectMock);

        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->resourceConnectionMock->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceConnectionMock->method('getTableName')
            ->with(SchemaInterface::EMAIL_COUPON_JOB_TABLE)
            ->willReturn(SchemaInterface::EMAIL_COUPON_JOB_TABLE);

        $this->subject = new BulkCouponMetricData($this->resourceConnectionMock);
    }

    public function testGetMetricDataReturnsEmptyWhenTableDoesNotExist(): void
    {
        $this->connectionMock->method('isTableExists')->willReturn(false);

        $result = $this->subject->getMetricData(1);

        $this->assertSame([
            'last_completed_at' => null,
            'total_coupon_jobs' => 0,
            'total_coupons'     => 0,
        ], $result);
    }

    public function testGetMetricDataReturnsEmptyOnException(): void
    {
        $this->connectionMock->method('isTableExists')->willReturn(true);
        $this->connectionMock->method('fetchOne')->willThrowException(new \Exception('DB error'));

        $result = $this->subject->getMetricData(1);

        $this->assertSame([
            'last_completed_at' => null,
            'total_coupon_jobs' => 0,
            'total_coupons'     => 0,
        ], $result);
    }

    public function testGetMetricDataReturnsAggregatedStats(): void
    {
        $this->connectionMock->method('isTableExists')->willReturn(true);
        $this->connectionMock->method('fetchOne')->willReturnOnConsecutiveCalls(
            '2026-08-03 12:00:00',
            '5',
            '135'
        );

        $result = $this->subject->getMetricData(1);

        $this->assertSame('2026-08-03 12:00:00', $result['last_completed_at']);
        $this->assertSame(5, $result['total_coupon_jobs']);
        $this->assertSame(135, $result['total_coupons']);
    }

    public function testGetMetricDataNullsLastCompletedAtWhenNoRows(): void
    {
        $this->connectionMock->method('isTableExists')->willReturn(true);
        $this->connectionMock->method('fetchOne')->willReturnOnConsecutiveCalls(
            false,  // MAX(updated_at) returns false when no rows match
            '0',
            '0'
        );

        $result = $this->subject->getMetricData(1);

        $this->assertNull($result['last_completed_at']);
    }
}
