<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterQueueManager;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkFactory as ContactBulkFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\DeleteFactory as ContactDeleteFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\UpdateFactory as ContactUpdateFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\BulkFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\DeleteFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData\UpdateFactory;
use PHPUnit\Framework\TestCase;

class ImporterQueueManagerTest extends TestCase
{
    /**
     * @var ImporterQueueManager
     */
    private $importerQueueManager;

    /**
     * @var ContactBulkFactory
     */
    private $contactBulkFactoryMock;

    /**
     * @var ContactUpdateFactory
     */
    private $contactUpdateFactoryMock;

    /**
     * @var ContactDeleteFactory
     */
    private $contactDeleteFactoryMock;

    /**
     * @var BulkFactory
     */
    private $bulkFactoryMock;

    /**
     * @var UpdateFactory
     */
    private $updateFactoryMock;

    /**
     * @var DeleteFactory
     */
    private $deleteFactoryMock;

    protected function setUp() :void
    {
        $this->contactBulkFactoryMock = $this->createMock(ContactBulkFactory::class);
        $this->contactUpdateFactoryMock = $this->createMock(ContactUpdateFactory::class);
        $this->contactDeleteFactoryMock = $this->createMock(ContactDeleteFactory::class);
        $this->bulkFactoryMock = $this->createMock(BulkFactory::class);
        $this->updateFactoryMock = $this->createMock(UpdateFactory::class);
        $this->deleteFactoryMock = $this->createMock(DeleteFactory::class);

        $this->importerQueueManager = new ImporterQueueManager(
            $this->contactBulkFactoryMock,
            $this->contactUpdateFactoryMock,
            $this->contactDeleteFactoryMock,
            $this->bulkFactoryMock,
            $this->updateFactoryMock,
            $this->deleteFactoryMock
        );
    }

    public function testThatContactBulkHasTopPriority()
    {
        $bulkPriority = $this->importerQueueManager->getBulkQueue();
        $this->assertEquals($this->contactBulkFactoryMock, $bulkPriority[0]['model']);
    }
}
