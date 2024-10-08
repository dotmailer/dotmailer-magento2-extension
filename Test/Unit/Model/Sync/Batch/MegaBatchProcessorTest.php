<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Batch;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Record\RecordImportedStrategyInterface;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Sender\SenderStrategyInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Batch\Record\RecordImportedStrategyFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SenderStrategyFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkSaver;
use Magento\Framework\Stdlib\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MegaBatchProcessorTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var RecordImportedStrategyFactory|MockObject
     */
    private $recordImportedStrategyFactoryMock;

    /**
     * @var SenderStrategyFactory|MockObject
     */
    private $senderStrategyFactoryMock;

    /**
     * @var BulkSaver|MockObject
     */
    private $bulkSaverMock;

    /**
     * @var MegaBatchProcessor
     */
    private $megaBatchProcessor;

    /**
     * @var DateTime|MockObject
     */
    private $dateTimeMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->recordImportedStrategyFactoryMock = $this->createMock(RecordImportedStrategyFactory::class);
        $this->senderStrategyFactoryMock = $this->createMock(SenderStrategyFactory::class);
        $this->bulkSaverMock = $this->createMock(BulkSaver::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);

        $this->megaBatchProcessor = new MegaBatchProcessor(
            $this->loggerMock,
            $this->recordImportedStrategyFactoryMock,
            $this->senderStrategyFactoryMock,
            $this->bulkSaverMock,
            $this->dateTimeMock
        );
    }

    public function testCustomerBatchIsProcessed()
    {
        $batch = $this->getCustomersBatch();

        $senderStrategyInterfaceMock = $this->createMock(SenderStrategyInterface::class);
        $this->senderStrategyFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($senderStrategyInterfaceMock);

        $senderStrategyInterfaceMock->expects($this->once())
            ->method('setBatch')
            ->willReturn($senderStrategyInterfaceMock);

        $senderStrategyInterfaceMock->expects($this->once())
            ->method('setWebsiteId')
            ->willReturn($senderStrategyInterfaceMock);

        $senderStrategyInterfaceMock->expects($this->once())
            ->method('process')
            ->willReturn('c58c075f-3ae8-458d-8681-c7e55db08775');

        $this->bulkSaverMock->expects($this->once())
            ->method('addInProgressBatchToImportTable');

        $this->dateTimeMock->method('formatDate')->willReturn('2022-03-17 11:28:46');

        $this->loggerMock->expects($this->once())
            ->method('info');

        $recordImportedStrategyInterfaceMock = $this->createMock(RecordImportedStrategyInterface::class);
        $this->recordImportedStrategyFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($recordImportedStrategyInterfaceMock);

        $recordImportedStrategyInterfaceMock->expects($this->once())
            ->method('setRecords')
            ->willReturn($recordImportedStrategyInterfaceMock);

        $recordImportedStrategyInterfaceMock->expects($this->once())
            ->method('process');

        $this->megaBatchProcessor->process(
            $batch,
            2,
            'Customer'
        );
    }

    public function testThatEmptyBatchIsNotProcessed()
    {
        $batch = [];

        $this->senderStrategyFactoryMock->expects($this->never())
            ->method('create');

        $this->recordImportedStrategyFactoryMock->expects($this->never())
            ->method('create');

        $this->megaBatchProcessor->process(
            $batch,
            2,
            'Customers'
        );
    }

    public function testFailedBatchIsAddedToImportTable()
    {
        $batch = $this->getCustomersBatch();

        $senderStrategyInterfaceMock = $this->createMock(SenderStrategyInterface::class);
        $this->senderStrategyFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($senderStrategyInterfaceMock);

        $senderStrategyInterfaceMock->expects($this->once())
            ->method('setBatch')
            ->willReturn($senderStrategyInterfaceMock);

        $senderStrategyInterfaceMock->expects($this->once())
            ->method('setWebsiteId')
            ->willReturn($senderStrategyInterfaceMock);

        $senderStrategyInterfaceMock->expects($this->once())
            ->method('process')
            ->willThrowException($e = new ResponseValidationException('Error message'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with((string) $e);

        $this->bulkSaverMock->expects($this->once())
            ->method('addFailedBatchToImportTable');

        $recordImportedStrategyInterfaceMock = $this->createMock(RecordImportedStrategyInterface::class);
        $this->recordImportedStrategyFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($recordImportedStrategyInterfaceMock);

        $recordImportedStrategyInterfaceMock->expects($this->once())
            ->method('setRecords')
            ->willReturn($recordImportedStrategyInterfaceMock);

        $recordImportedStrategyInterfaceMock->expects($this->once())
            ->method('process');

        $this->megaBatchProcessor->process(
            $batch,
            2,
            'Customer'
        );
    }

    /**
     * Some customers.
     *
     * @return array[]
     */
    private function getCustomersBatch()
    {
        return [
            1 => ['chazco@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chaz', 'Kangaroo'],
            21 => ['chaz2@emailsim.io', 'Html', '1', 0, null, 0.0, 'Dave', 'Dot'],
            309 => ['chaz3@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chip', 'Chop'],
        ];
    }
}
