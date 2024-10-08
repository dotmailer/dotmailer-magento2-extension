<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkImportBuilder;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterQueueManager;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkFactory as ContactBulkFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkJson;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkJsonFactory as ContactBulkJsonFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\DeleteFactory as ContactDeleteFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\UpdateFactory as ContactUpdateFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\BulkJsonFactory as TransactionalBulkJsonFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\BulkFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\DeleteFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\UpdateFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkImportBuilderFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer;
use PHPUnit\Framework\TestCase;

class ImporterQueueManagerTest extends TestCase
{
    /**
     * @var ImporterQueueManager
     */
    private $importerQueueManager;

    /**
     * @var ContactBulkFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactBulkFactory;

    /**
     * @var ContactBulkJsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactBulkJsonFactory;

    /**
     * @var ContactUpdateFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactUpdateFactory;

    /**
     * @var ContactDeleteFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactDeleteFactory;

    /**
     * @var TransactionalBulkJsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionalBulkJsonFactory;

    /**
     * @var BulkFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $bulkFactory;

    /**
     * @var UpdateFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $updateFactory;

    /**
     * @var DeleteFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $deleteFactory;

    /**
     * @var BulkImportBuilderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $bulkImportBuilderFactoryMock;

    /**
     * @var BulkImportBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $bulkImportBuilderMock;

    protected function setUp(): void
    {
        $this->contactBulkFactory = $this->createMock(ContactBulkFactory::class);
        $this->contactBulkJsonFactory = $this->createMock(ContactBulkJsonFactory::class);
        $this->contactUpdateFactory = $this->createMock(ContactUpdateFactory::class);
        $this->contactDeleteFactory = $this->createMock(ContactDeleteFactory::class);
        $this->transactionalBulkJsonFactory = $this->createMock(TransactionalBulkJsonFactory::class);
        $this->bulkFactory = $this->createMock(BulkFactory::class);
        $this->updateFactory = $this->createMock(UpdateFactory::class);
        $this->deleteFactory = $this->createMock(DeleteFactory::class);
        $this->bulkImportBuilderMock = $this->createMock(BulkImportBuilder::class);
        $this->bulkImportBuilderFactoryMock = $this->createMock(BulkImportBuilderFactory::class);

        $this->importerQueueManager = new ImporterQueueManager(
            $this->contactBulkFactory,
            $this->contactBulkJsonFactory,
            $this->contactUpdateFactory,
            $this->contactDeleteFactory,
            $this->transactionalBulkJsonFactory,
            $this->bulkFactory,
            $this->updateFactory,
            $this->deleteFactory,
            $this->bulkImportBuilderFactoryMock
        );
    }

    public function testGetBulkQueue()
    {
        $this->bulkImportBuilderFactoryMock->method('create')->willReturn($this->bulkImportBuilderMock);

        $result = $this->importerQueueManager->getBulkQueue();

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
    }

    public function testGetBulkQueuePluggable()
    {
        $bulkClass = $this->createMock(BulkJson::class);
        $bulkImportBuilder = new BulkImportBuilder();

        $additional = [
            "negotiableQuotes",
            $bulkImportBuilder->setModel($bulkClass)
                ->setMode(ImporterModel::MODE_BULK)
                ->setType(['testPluggableType'])
                ->setLimit(Importer::TOTAL_IMPORT_SYNC_LIMIT)
        ];

        $this->bulkImportBuilderFactoryMock->expects($this->exactly(5))
            ->method('create')
            ->willReturn($this->bulkImportBuilderMock);

        $this->bulkImportBuilderMock->expects($this->exactly(5))
            ->method('setModel')
            ->willReturn($this->bulkImportBuilderMock);

        $this->bulkImportBuilderMock->expects($this->exactly(4))
            ->method('setType')
            ->willReturn($this->bulkImportBuilderMock);

        $result = $this->importerQueueManager->getBulkQueue($additional);
        $this->assertEquals($bulkImportBuilder->build(), $result[5]);
    }

    public function testGetSingleQueue()
    {
        $result = $this->importerQueueManager->getSingleQueue();

        $this->assertIsArray($result);
        $this->assertCount(7, $result);
    }

    public function testBulkQueueItemsHaveExpectedStructure()
    {
        $this->bulkImportBuilderFactoryMock->expects($this->exactly(4))
            ->method('create')
            ->willReturn($this->bulkImportBuilderMock);
        $this->bulkImportBuilderMock->expects($this->exactly(4))
            ->method('setModel')
            ->willReturn($this->bulkImportBuilderMock);
        $this->bulkImportBuilderMock->expects($this->exactly(4))
            ->method('setType')
            ->willReturn($this->bulkImportBuilderMock);
        $this->bulkImportBuilderMock->expects($this->exactly(2))
            ->method('setLimit')
            ->willReturn($this->bulkImportBuilderMock);
        $this->bulkImportBuilderMock->expects($this->exactly(3))
            ->method('setMode')
            ->willReturn($this->bulkImportBuilderMock);

        $this->bulkImportBuilderMock->expects($this->exactly(4))
            ->method('build')
            ->willReturn([
                'model' => $this->bulkFactory,
                'mode' => ImporterModel::MODE_BULK,
                'type' => [],
                'limit' => Importer::TOTAL_IMPORT_SYNC_LIMIT
            ]);

        $bulkQueue = $this->importerQueueManager->getBulkQueue();
        $expectedKeys = [
            'model',
            'mode',
            'type',
            'limit'
        ];

        foreach ($bulkQueue as $index => $queueItem) {
            $missingKeys = array_diff_key(array_flip($expectedKeys), $queueItem);
            $this->assertEmpty(
                $missingKeys,
                sprintf(
                    'The item at index %d in the bulkQueue array is missing the following keys: %s',
                    $index,
                    implode(', ', array_keys($missingKeys))
                )
            );
        }
    }
}
