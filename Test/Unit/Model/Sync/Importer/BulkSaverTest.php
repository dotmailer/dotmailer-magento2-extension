<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob\ImporterLink as CouponJobImporterLinkResource;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkSaver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class BulkSaverTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ImporterFactory|MockObject
     */
    private $importerFactoryMock;

    /**
     * @var CouponJobImporterLinkResource|MockObject
     */
    private $linkResourceMock;

    /**
     * @var Importer|MockObject
     */
    private $importerMock;

    /**
     * @var BulkSaver
     */
    private $bulkSaver;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->importerFactoryMock = $this->createMock(ImporterFactory::class);
        $this->linkResourceMock = $this->createMock(CouponJobImporterLinkResource::class);

        $this->importerMock = $this->getMockBuilder(Importer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addToImporterQueue', 'getId'])
            ->getMock();
        $this->importerMock->method('addToImporterQueue')->willReturnSelf();
        $this->importerMock->method('getId')->willReturn(10);

        $this->importerFactoryMock->method('create')->willReturn($this->importerMock);

        $this->bulkSaver = new BulkSaver(
            $this->loggerMock,
            $this->importerFactoryMock,
            $this->linkResourceMock
        );
    }

    /**
     * A COUPON_JOB in-progress batch records the coupon-job-to-importer link.
     */
    public function testInProgressBatchLinksCouponJobImporter(): void
    {
        $this->linkResourceMock->expects($this->once())
            ->method('linkImporter')
            ->with(55, 10);

        $this->bulkSaver->addInProgressBatchToImportTable(
            ['coupon_job_id' => 55, 'records' => ['a@test.com' => []]],
            1,
            'import-1',
            Importer::IMPORT_TYPE_COUPON_JOB,
            '2026-07-23 00:00:00',
            Importer::MODE_BULK_JSON
        );
    }

    /**
     * A COUPON_JOB failed batch also records the link.
     */
    public function testFailedBatchLinksCouponJobImporter(): void
    {
        $this->linkResourceMock->expects($this->once())
            ->method('linkImporter')
            ->with(55, 10);

        $this->bulkSaver->addFailedBatchToImportTable(
            ['coupon_job_id' => 55, 'records' => ['a@test.com' => []]],
            1,
            'some failure',
            Importer::IMPORT_TYPE_COUPON_JOB,
            Importer::MODE_BULK_JSON
        );
    }

    /**
     * Non-COUPON_JOB batches never touch the link table.
     */
    public function testSkipsLinkForNonCouponJobType(): void
    {
        $this->linkResourceMock->expects($this->never())
            ->method('linkImporter');

        $this->bulkSaver->addInProgressBatchToImportTable(
            ['some' => 'batch'],
            1,
            'import-1',
            Importer::IMPORT_TYPE_CONTACT,
            '2026-07-23 00:00:00',
            Importer::MODE_BULK_JSON
        );
    }

    /**
     * A COUPON_JOB batch with no resolvable coupon_job_id is skipped (no link, no error).
     */
    public function testSkipsLinkWhenCouponJobIdMissing(): void
    {
        $this->linkResourceMock->expects($this->never())
            ->method('linkImporter');

        $this->bulkSaver->addInProgressBatchToImportTable(
            ['records' => ['a@test.com' => []]],
            1,
            'import-1',
            Importer::IMPORT_TYPE_COUPON_JOB,
            '2026-07-23 00:00:00',
            Importer::MODE_BULK_JSON
        );
    }

    /**
     * A failure while linking is logged and swallowed, never thrown.
     */
    public function testLinkFailureIsLoggedNotThrown(): void
    {
        $this->linkResourceMock->method('linkImporter')
            ->willThrowException(new \Exception('duplicate key'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('could not link coupon job 55 to importer 10'));

        $this->bulkSaver->addInProgressBatchToImportTable(
            ['coupon_job_id' => 55, 'records' => ['a@test.com' => []]],
            1,
            'import-1',
            Importer::IMPORT_TYPE_COUPON_JOB,
            '2026-07-23 00:00:00',
            Importer::MODE_BULK_JSON
        );
    }
}
