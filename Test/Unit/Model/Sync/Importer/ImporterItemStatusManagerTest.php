<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterItemStatusManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ImporterItemStatusManagerTest extends TestCase
{
    /**
     * @var ImporterResource|MockObject
     */
    private $importerResourceMock;

    /**
     * @var ImporterModel|MockObject
     */
    private $itemMock;

    /**
     * @var ImporterItemStatusManager
     */
    private $importerItemStatusManager;

    protected function setUp(): void
    {
        $this->importerResourceMock = $this->createMock(ImporterResource::class);
        $this->itemMock = $this->createMock(ImporterModel::class);

        $this->importerItemStatusManager = new ImporterItemStatusManager($this->importerResourceMock);
    }

    public function testFailedStatusesAreRecognised(): void
    {
        foreach (ImporterItemStatusManager::FAILED_IMPORT_STATUSES as $status) {
            $this->assertTrue($this->importerItemStatusManager->isFailedStatus($status));
        }
    }

    public function testNonFailedStatusesAreNotRecognisedAsFailures(): void
    {
        $this->assertFalse($this->importerItemStatusManager->isFailedStatus('Finished'));
        $this->assertFalse($this->importerItemStatusManager->isFailedStatus('NotFinished'));
        $this->assertFalse($this->importerItemStatusManager->isFailedStatus(null));
    }

    public function testMarkImportedSetsImportedStatusFinishedDateAndClearsMessage(): void
    {
        $calls = [];

        $this->itemMock->method('__call')
            ->willReturnCallback(function (string $method, array $args) use (&$calls) {
                $calls[$method] = $args[0] ?? null;
                return $this->itemMock;
            });

        $item = $this->importerItemStatusManager->markImported($this->itemMock);

        $this->assertSame($this->itemMock, $item);
        $this->assertSame(ImporterModel::IMPORTED, $calls['setImportStatus']);
        $this->assertSame('', $calls['setMessage']);
        $this->assertArrayHasKey('setImportFinished', $calls);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $calls['setImportFinished']
        );
    }

    public function testMarkFailedSetsFailedStatusAndMessage(): void
    {
        $calls = [];

        $this->itemMock->method('__call')
            ->willReturnCallback(function (string $method, array $args) use (&$calls) {
                $calls[$method] = $args[0] ?? null;
                return $this->itemMock;
            });

        $item = $this->importerItemStatusManager->markFailed($this->itemMock, 'Something went wrong');

        $this->assertSame($this->itemMock, $item);
        $this->assertSame(ImporterModel::FAILED, $calls['setImportStatus']);
        $this->assertSame('Something went wrong', $calls['setMessage']);
        $this->assertArrayNotHasKey('setImportFinished', $calls);
    }

    public function testSaveDelegatesToImporterResource(): void
    {
        $this->importerResourceMock->expects($this->once())
            ->method('save')
            ->with($this->itemMock);

        $this->importerItemStatusManager->save($this->itemMock);
    }
}
