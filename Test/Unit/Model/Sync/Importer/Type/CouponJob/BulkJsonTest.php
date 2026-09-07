<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer\Type\CouponJob;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Api\Model\CouponJob\CouponJobInterface;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Sender\SenderStrategyInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob\Contracts\JobCancellerInterface;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob as CouponJobResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SenderStrategyFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterItemStatusManager;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\CouponJob\BulkJson;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\V3ItemPostProcessorFactory;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class BulkJsonTest extends TestCase
{
    /**
     * @var V3ItemPostProcessorFactory|MockObject
     */
    private $postProcessorMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var SenderStrategyFactory|MockObject
     */
    private $senderStrategyFactoryMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Importer|MockObject
     */
    private $itemMock;

    /**
     * @var CouponJobResource|MockObject
     */
    private $couponJobResourceMock;

    /**
     * @var ImporterItemStatusManager|MockObject
     */
    private $importerItemStatusManagerMock;

    /**
     * @var BulkJson
     */
    private $bulkJson;

    protected function setUp(): void
    {
        $this->postProcessorMock = $this->createMock(V3ItemPostProcessorFactory::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->senderStrategyFactoryMock = $this->createMock(SenderStrategyFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->couponJobResourceMock = $this->createMock(CouponJobResource::class);
        $this->importerItemStatusManagerMock = $this->createMock(ImporterItemStatusManager::class);

        $this->itemMock = $this->getMockBuilder(Importer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getId'])
            ->getMock();

        $this->itemMock->method('getId')->willReturn(7);
        $this->itemMock->method('__call')
            ->willReturnCallback(function (string $method, array $args = []) {
                return match ($method) {
                    'getImportData' => 'serialized',
                    'getImportType' => Importer::IMPORT_TYPE_COUPON_JOB,
                    'getWebsiteId' => 1,
                    default => null,
                };
            });

        $this->bulkJson = new BulkJson(
            $this->postProcessorMock,
            $this->serializerMock,
            $this->senderStrategyFactoryMock,
            $this->loggerMock,
            $this->couponJobResourceMock,
            $this->importerItemStatusManagerMock
        );
    }

    /**
     * A batch belonging to a cancelled coupon job is never re-sent.
     *
     * The row is removed from the collection before AbstractItemSyncer sees it, so
     * the post processor never runs, and it is stamped with the shared cancellation
     * message so the bulk queue does not pick it up again.
     */
    public function testSyncSkipsBatchesForCancelledCouponJob(): void
    {
        $this->serializerMock->method('unserialize')->willReturn(['coupon_job_id' => 55]);

        $this->couponJobResourceMock->expects($this->once())
            ->method('getStatusById')
            ->with(55)
            ->willReturn(CouponJobInterface::STATUS_CANCELLED);

        $items = [7 => $this->itemMock];

        $collectionMock = $this->createMock(ImporterCollection::class);
        $collectionMock->method('getIterator')
            ->willReturnCallback(function () use (&$items) {
                return new \ArrayIterator($items);
            });
        $collectionMock->expects($this->once())
            ->method('removeItemByKey')
            ->willReturnCallback(function ($key) use (&$items, $collectionMock) {
                unset($items[$key]);
                return $collectionMock;
            });

        $this->importerItemStatusManagerMock->expects($this->once())
            ->method('markFailed')
            ->with($this->itemMock, JobCancellerInterface::CANCELLED_MESSAGE);
        $this->importerItemStatusManagerMock->expects($this->once())
            ->method('save')
            ->with($this->itemMock);

        $this->senderStrategyFactoryMock->expects($this->never())->method('create');
        $this->postProcessorMock->expects($this->never())->method('create');

        $this->bulkJson->sync($collectionMock);

        $this->assertSame([], $items);
    }

    /**
     * A row with no records logs a warning, delegates nothing and returns an empty id.
     */
    public function testProcessReturnsEmptyAndWarnsWhenNoRecords(): void
    {
        $this->serializerMock->method('unserialize')->willReturn(['coupon_job_id' => 55]);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('importer row 7 has no records'));

        $this->senderStrategyFactoryMock->expects($this->never())->method('create');

        $this->assertSame('', $this->bulkJson->process($this->itemMock));
    }

    /**
     * A row with records hydrates each record into an SdkContact and delegates the
     * wrapped batch to the coupon-job sender strategy, returning its import id.
     */
    public function testProcessHydratesRecordsAndDelegatesToSenderStrategy(): void
    {

        $this->serializerMock->method('unserialize')->willReturn([
            'coupon_job_id' => 55,
            'records' => [
                'a@test.com' => ['identifiers' => ['email' => 'a@test.com']],
            ],
        ]);

        $strategyMock = $this->createMock(SenderStrategyInterface::class);

        $strategyMock->expects($this->once())
            ->method('setBatch')
            ->willReturnCallback(function (array $batch) use ($strategyMock) {
                $this->assertArrayHasKey('records', $batch);
                $this->assertInstanceOf(SdkContact::class, $batch['records']['a@test.com']);
                return $strategyMock;
            });
        $strategyMock->expects($this->once())
            ->method('setWebsiteId')
            ->with(1)
            ->willReturnSelf();
        $strategyMock->expects($this->once())
            ->method('process')
            ->willReturn('imp-1');

        $this->senderStrategyFactoryMock->expects($this->once())
            ->method('create')
            ->with(Importer::IMPORT_TYPE_COUPON_JOB)
            ->willReturn($strategyMock);

        $this->assertSame('imp-1', $this->bulkJson->process($this->itemMock));
    }
}
