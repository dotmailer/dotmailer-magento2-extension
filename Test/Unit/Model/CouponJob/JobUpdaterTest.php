<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\CouponJob;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob;
use Dotdigitalgroup\Email\Model\CouponJobFactory;
use Dotdigitalgroup\Email\Model\CouponJob\JobUpdater;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class JobUpdaterTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var CouponJobFactory|MockObject
     */
    private $couponJobFactoryMock;

    /**
     * @var CouponJobResource|MockObject
     */
    private $couponJobResourceMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var Select|MockObject
     */
    private $selectMock;

    /**
     * @var CouponJob|MockObject
     */
    private $couponJobMock;

    /**
     * @var JobUpdater
     */
    private $jobUpdater;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->couponJobFactoryMock = $this->createMock(CouponJobFactory::class);
        $this->couponJobResourceMock = $this->createMock(CouponJobResource::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->selectMock = $this->createMock(Select::class);
        $this->couponJobMock = $this->createMock(CouponJob::class);

        $this->selectMock->method('from')->willReturnSelf();
        $this->selectMock->method('where')->willReturnSelf();
        $this->selectMock->method('forUpdate')->willReturnSelf();

        $this->connectionMock->method('select')->willReturn($this->selectMock);
        $this->couponJobResourceMock->method('getConnection')->willReturn($this->connectionMock);
        $this->couponJobResourceMock->method('getMainTable')->willReturn('email_coupon_job');
        $this->couponJobFactoryMock->method('create')->willReturn($this->couponJobMock);

        $this->jobUpdater = new JobUpdater(
            $this->loggerMock,
            $this->couponJobFactoryMock,
            $this->couponJobResourceMock
        );
    }

    /**
     * The happy path: the row is locked, the mutator is applied and the job is saved.
     */
    public function testUpdateAppliesMutatorAndCommitsOnSuccess(): void
    {
        $this->connectionMock->expects($this->once())->method('beginTransaction');
        $this->connectionMock->method('fetchOne')->willReturn('5');
        $this->connectionMock->expects($this->once())->method('commit');
        $this->connectionMock->expects($this->never())->method('rollBack');

        $this->couponJobResourceMock->expects($this->once())
            ->method('load')
            ->with($this->couponJobMock, 5);
        $this->couponJobResourceMock->expects($this->once())
            ->method('save')
            ->with($this->couponJobMock);

        $mutatorCalledWith = null;
        $result = $this->jobUpdater->update(5, function ($job) use (&$mutatorCalledWith) {
            $mutatorCalledWith = $job;
        });

        $this->assertTrue($result);
        $this->assertSame($this->couponJobMock, $mutatorCalledWith);
    }

    /**
     * When the row does not exist the transaction is rolled back, the mutator is never
     * invoked, an error is logged and false is returned.
     */
    public function testUpdateReturnsFalseAndRollsBackWhenJobNotFound(): void
    {
        $this->connectionMock->expects($this->once())->method('beginTransaction');
        $this->connectionMock->method('fetchOne')->willReturn(false);
        $this->connectionMock->expects($this->once())->method('rollBack');
        $this->connectionMock->expects($this->never())->method('commit');

        $this->couponJobResourceMock->expects($this->never())->method('load');
        $this->couponJobResourceMock->expects($this->never())->method('save');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('CouponJob 5 not found'));

        $mutatorCalled = false;
        $result = $this->jobUpdater->update(5, function () use (&$mutatorCalled) {
            $mutatorCalled = true;
        });

        $this->assertFalse($result);
        $this->assertFalse($mutatorCalled);
    }

    /**
     * Any throwable inside the locked section is caught, rolled back and logged, and
     * the method returns false rather than propagating the exception.
     */
    public function testUpdateRollsBackAndReturnsFalseOnException(): void
    {
        $this->connectionMock->expects($this->once())->method('beginTransaction');
        $this->connectionMock->method('fetchOne')->willReturn('5');
        $this->connectionMock->expects($this->once())->method('rollBack');
        $this->connectionMock->expects($this->never())->method('commit');

        $this->couponJobResourceMock->method('save')
            ->willThrowException(new \Exception('db down'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error updating job 5: db down'));

        $result = $this->jobUpdater->update(5, function () {
            // no-op mutator
        });

        $this->assertFalse($result);
    }
}
