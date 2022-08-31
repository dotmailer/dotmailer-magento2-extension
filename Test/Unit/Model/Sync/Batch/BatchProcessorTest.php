<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Batch;

use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory as ContactResourceFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\CustomerBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Batch\GuestBatchProcessor;
use PHPUnit\Framework\TestCase;

class BatchProcessorTest extends TestCase
{
    /**
     * @var File|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileHelperMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ImporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerFactoryMock;

    /**
     * @var Importer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerMock;

    /**
     * @var ContactResourceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactResourceFactoryMock;

    /**
     * @var ContactResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactResourceMock;

    /**
     * @var CustomerBatchProcessor
     */
    private $batchProcessor;

    /**
     * @var GuestBatchProcessor
     */
    private $guestBatchProcessor;

    protected function setUp(): void
    {
        $this->fileHelperMock = $this->createMock(File::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->importerMock = $this->createMock(Importer::class);
        $this->importerFactoryMock = $this->createMock(ImporterFactory::class);
        $this->contactResourceFactoryMock = $this->createMock(ContactResourceFactory::class);
        $this->contactResourceMock = $this->createMock(ContactResource::class);

        $this->batchProcessor = new CustomerBatchProcessor(
            $this->fileHelperMock,
            $this->importerFactoryMock,
            $this->loggerMock,
            $this->contactResourceFactoryMock
        );

        $this->guestBatchProcessor = new GuestBatchProcessor(
            $this->fileHelperMock,
            $this->importerFactoryMock,
            $this->loggerMock,
            $this->contactResourceFactoryMock
        );
    }

    /**
     * @return void
     */
    public function testBatchIsProcessed()
    {
        $batch = $this->getCustomersBatch();

        $this->fileHelperMock->expects($this->exactly(count($batch)))
            ->method('outputCSV');

        $this->importerFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->importerMock);

        $this->importerMock->expects($this->atLeastOnce())
            ->method('registerQueue')
            ->willReturn(true);

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('info');

        $this->contactResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->contactResourceMock);

        $this->contactResourceMock->expects($this->atLeastOnce())
            ->method('setContactsImportedByIds');

        $this->batchProcessor->process(
            $batch,
            2,
            'chaz_customers_17_03_2022_112846.csv'
        );
    }

    /**
     * @return void
     */
    public function testThatEmptyBatchIsNotProcessed()
    {
        $batch = [];

        $this->fileHelperMock->expects($this->never())
            ->method('outputCSV');

        $this->importerMock->expects($this->never())
            ->method('registerQueue')
            ->willReturn(true);

        $this->contactResourceMock->expects($this->never())
            ->method('setContactsImportedByIds');

        $this->batchProcessor->process(
            $batch,
            2,
            'chaz_customers_17_03_2022_112846.csv'
        );
    }

    /**
     * @return void
     */
    public function testGuestBatchIsProcessed()
    {
        $batch = $this->getGuestsBatch();

        $this->fileHelperMock->expects($this->exactly(count($batch)))
            ->method('outputCSV');

        $this->importerFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->importerMock);

        $this->importerMock->expects($this->atLeastOnce())
            ->method('registerQueue')
            ->willReturn(true);

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('info');

        $this->contactResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->contactResourceMock);

        $this->contactResourceMock->expects($this->atLeastOnce())
            ->method('setContactsImportedByIds');

        $this->guestBatchProcessor->process(
            $batch,
            2,
            'chaz_guests_17_03_2022_112846_hash.csv'
        );
    }

    /**
     * @return void
     */
    public function testThatEmptyGuestBatchIsNotProcessed()
    {
        $batch = [];

        $this->fileHelperMock->expects($this->never())
            ->method('outputCSV');

        $this->importerMock->expects($this->never())
            ->method('registerQueue')
            ->willReturn(true);

        $this->contactResourceMock->expects($this->never())
            ->method('setContactsImportedByIds');

        $this->batchProcessor->process(
            $batch,
            2,
            'chaz_customers_17_03_2022_112846.csv'
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

    /**
     * Some guests.
     *
     * @return array[]
     */
    private function getGuestsBatch()
    {
        return [
            1 => ["Default Store View","Main Website","chazguest@emailsim","Html"],
            5 => ["Default Store View","Main Website","chazguest2@emailsim","Html"],
            23 => ["Default Store View","Main Website","chazguest3@emailsim","Html"],
        ];
    }
}
