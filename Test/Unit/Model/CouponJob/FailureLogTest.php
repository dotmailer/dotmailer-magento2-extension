<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\CouponJob;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\CouponJob\FailureLog;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\File\WriteInterface as FileWriteInterface;
use Magento\Framework\Filesystem\Io\File as IoFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class FailureLogTest extends TestCase
{
    /**
     * @var Filesystem|MockObject
     */
    private $filesystemMock;

    /**
     * @var WriteInterface|MockObject
     */
    private $directoryMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var IoFile|MockObject
     */
    private $ioFileMock;

    /**
     * @var FailureLog
     */
    private $failureLog;

    protected function setUp(): void
    {
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->directoryMock = $this->createMock(WriteInterface::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->ioFileMock = $this->createMock(IoFile::class);

        $this->filesystemMock->method('getDirectoryWrite')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($this->directoryMock);

        // Mirror the real IoFile::getPathInfo for numeric batch-key ordering.
        $this->ioFileMock->method('getPathInfo')
            ->willReturnCallback(function ($path) {
                return pathinfo($path);
            });

        $this->failureLog = new FailureLog($this->filesystemMock, $this->loggerMock, $this->ioFileMock);
    }

    public function testAppendWritesOneJsonLinePerRecordToFlatMessageFileUnderLock(): void
    {
        $streamMock = $this->createMock(FileWriteInterface::class);
        $streamMock->expects($this->once())->method('lock');

        $written = '';
        $streamMock->method('write')->willReturnCallback(function ($payload) use (&$written) {
            $written .= $payload;
            return strlen($payload);
        });
        $streamMock->expects($this->once())->method('unlock');
        $streamMock->expects($this->once())->method('close');

        $this->directoryMock->expects($this->once())
            ->method('openFile')
            ->with('dotdigital-bulk-coupons/55.messages.log', 'a')
            ->willReturn($streamMock);

        $this->failureLog->append(55, FailureLog::CATEGORY_MESSAGES, [
            ['id' => 'coupon_sync_1', 'error_message' => 'boom', 'failed_at' => 'now'],
            ['id' => 'coupon_sync_2', 'error_message' => 'bang', 'failed_at' => 'now'],
        ]);

        $lines = array_values(array_filter(explode("\n", $written)));
        $this->assertCount(2, $lines);
        $this->assertSame(
            ['id' => 'coupon_sync_1', 'error_message' => 'boom', 'failed_at' => 'now'],
            json_decode($lines[0], true)
        );
    }

    public function testAppendIgnoresEmptyPayload(): void
    {
        $this->directoryMock->expects($this->never())->method('openFile');
        $this->failureLog->append(55, FailureLog::CATEGORY_MESSAGES, []);
    }

    public function testAppendSwallowsAndLogsWriteErrors(): void
    {
        $this->directoryMock->method('openFile')
            ->willThrowException(new \RuntimeException('disk full'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('failed to append'));

        // No exception should propagate.
        $this->failureLog->append(55, FailureLog::CATEGORY_MESSAGES, [['id' => 'x']]);
    }

    public function testReplaceBatchTruncatesAndWritesPerBatchImportFile(): void
    {
        $streamMock = $this->createMock(FileWriteInterface::class);
        $streamMock->expects($this->once())->method('lock');

        $written = '';
        $streamMock->method('write')->willReturnCallback(function ($payload) use (&$written) {
            $written .= $payload;
            return strlen($payload);
        });
        $streamMock->expects($this->once())->method('unlock');
        $streamMock->expects($this->once())->method('close');

        // 'w' (truncate) keyed by importer id, so a retried batch replaces its detail.
        $this->directoryMock->expects($this->once())
            ->method('openFile')
            ->with('dotdigital-bulk-coupons/55.imports.10.log', 'w')
            ->willReturn($streamMock);

        $this->failureLog->replaceBatch(55, FailureLog::CATEGORY_IMPORTS, 10, [
            ['email' => 'a@x.com', 'failure_code' => 'BLOCKED', 'description' => 'nope'],
        ]);

        $lines = array_values(array_filter(explode("\n", $written)));
        $this->assertCount(1, $lines);
        $this->assertSame(
            ['email' => 'a@x.com', 'failure_code' => 'BLOCKED', 'description' => 'nope'],
            json_decode($lines[0], true)
        );
    }

    public function testReplaceBatchWithEmptyLinesDeletesExistingBatchFile(): void
    {
        $this->directoryMock->method('isExist')
            ->with('dotdigital-bulk-coupons/55.imports.10.log')
            ->willReturn(true);
        $this->directoryMock->expects($this->once())
            ->method('delete')
            ->with('dotdigital-bulk-coupons/55.imports.10.log');
        $this->directoryMock->expects($this->never())->method('openFile');

        $this->failureLog->replaceBatch(55, FailureLog::CATEGORY_IMPORTS, 10, []);
    }

    public function testReplaceBatchWithEmptyLinesDoesNothingWhenFileAbsent(): void
    {
        $this->directoryMock->method('isExist')->willReturn(false);
        $this->directoryMock->expects($this->never())->method('delete');
        $this->directoryMock->expects($this->never())->method('openFile');

        $this->failureLog->replaceBatch(55, FailureLog::CATEGORY_IMPORTS, 10, []);
    }

    public function testCountAndReadStreamPagesFromFlatMessageFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'ddgfl_');
        $rows = [];
        for ($i = 1; $i <= 5; $i++) {
            $rows[] = json_encode(['id' => "coupon_sync_$i", 'error_message' => "e$i", 'failed_at' => 'now']);
        }
        file_put_contents($tmpFile, implode("\n", $rows) . "\n");

        $this->directoryMock->method('isExist')
            ->with('dotdigital-bulk-coupons/55.messages.log')
            ->willReturn(true);
        $this->directoryMock->method('getAbsolutePath')
            ->with('dotdigital-bulk-coupons/55.messages.log')
            ->willReturn($tmpFile);

        try {
            $this->assertSame(5, $this->failureLog->count(55, FailureLog::CATEGORY_MESSAGES));

            $page = $this->failureLog->read(55, FailureLog::CATEGORY_MESSAGES, 2, 2);
            $this->assertCount(2, $page);
            $this->assertSame('coupon_sync_3', $page[0]['id']);
            $this->assertSame('coupon_sync_4', $page[1]['id']);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testCountAndReadAggregateAcrossPartitionedImportBatchFilesInBatchOrder(): void
    {
        // Batch 2 has 1 line; batch 10 has 2 lines. They must be read in batch-key order.
        $batch2 = tempnam(sys_get_temp_dir(), 'ddgfl2_');
        file_put_contents($batch2, json_encode(['email' => 'b2@x.com']) . "\n");

        $batch10 = tempnam(sys_get_temp_dir(), 'ddgfl10_');
        file_put_contents(
            $batch10,
            json_encode(['email' => 'b10a@x.com']) . "\n" . json_encode(['email' => 'b10b@x.com']) . "\n"
        );

        $dirPath = 'dotdigital-bulk-coupons';
        $path2 = $dirPath . '/55.imports.2.log';
        $path10 = $dirPath . '/55.imports.10.log';

        $this->directoryMock->method('isExist')
            ->willReturnCallback(function ($path) use ($dirPath, $path2, $path10) {
                return in_array($path, [$dirPath, $path2, $path10], true);
            });

        // Returned out of order to prove the reader sorts by batch key.
        $this->directoryMock->method('read')
            ->with($dirPath)
            ->willReturn([$path10, $path2]);

        $this->directoryMock->method('getAbsolutePath')
            ->willReturnCallback(function ($path) use ($path2, $path10, $batch2, $batch10) {
                return $path === $path2 ? $batch2 : ($path === $path10 ? $batch10 : '');
            });

        try {
            $this->assertSame(3, $this->failureLog->count(55, FailureLog::CATEGORY_IMPORTS));

            $all = $this->failureLog->read(55, FailureLog::CATEGORY_IMPORTS, 0, 10);
            $this->assertSame(
                ['b2@x.com', 'b10a@x.com', 'b10b@x.com'],
                array_column($all, 'email')
            );
        } finally {
            foreach ([$batch2, $batch10] as $f) {
                if (file_exists($f)) {
                    unlink($f);
                }
            }
        }
    }

    public function testReadReturnsEmptyWhenNoFilesExist(): void
    {
        $this->directoryMock->method('isExist')->willReturn(false);

        $this->assertSame(0, $this->failureLog->count(55, FailureLog::CATEGORY_IMPORTS));
        $this->assertSame([], $this->failureLog->read(55, FailureLog::CATEGORY_IMPORTS, 0, 10));
    }

    public function testUnknownCategoryIsLoggedNotThrownOnReplaceBatch(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('failed to replaceBatch'));

        $this->failureLog->replaceBatch(55, 'nonsense', 10, [['x' => 1]]);
    }
}
