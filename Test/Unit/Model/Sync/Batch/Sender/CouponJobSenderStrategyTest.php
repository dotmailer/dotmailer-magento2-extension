<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Batch\Sender;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\CouponJobSenderStrategy;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImportResponseHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CouponJobSenderStrategyTest extends TestCase
{
    public function testRecordCountUsesNestedCouponJobRecords(): void
    {
        $strategy = new CouponJobSenderStrategy(
            $this->createMock(ClientFactory::class),
            $this->createMock(Logger::class),
            $this->createMock(ImportResponseHandler::class)
        );

        $strategy->setBatch([
            'coupon_job_id' => 8,
            'records' => [
                'first@example.com' => [],
                'second@example.com' => [],
                'third@example.com' => [],
            ],
        ]);

        $this->assertSame(3, $strategy->getRecordCount());
    }

    public function testRecordCountIsZeroWhenCouponJobRecordsAreMissing(): void
    {
        $strategy = new CouponJobSenderStrategy(
            $this->createMock(ClientFactory::class),
            $this->createMock(Logger::class),
            $this->createMock(ImportResponseHandler::class)
        );

        $strategy->setBatch(['coupon_job_id' => 8]);

        $this->assertSame(0, $strategy->getRecordCount());
    }
}
