<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigital\Resources\AbstractResource;
use Dotdigital\V3\Resources\Contacts;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\Sync\Importer\V3InProgressImportResponseHandler;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler\V3ImporterReportHandler;
use Dotdigital\V3\Models\Contact\Import;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class V3InProgressImportResponseHandlerTest extends TestCase
{
    /**
     * @var ImporterResource|ImporterResource&MockObject|MockObject
     */
    private $importerResourceMock;

    /**
     * @var Logger|Logger&MockObject|MockObject
     */
    private $loggerMock;

    /**
     * @var V3InProgressImportResponseHandler
     */
    private $v3ProgressHandler;

    /**
     * @var Client|Client&MockObject|MockObject
     */
    private $v3ClientMock;

    /**
     * @var ImporterCollection|ImporterCollection&MockObject|MockObject
     */
    private $importerCollectionMock;

    /**
     * @var Importer|Importer&MockObject|MockObject
     */
    private $importerModelMock;

    /**
     * @var AbstractResource&MockObject|MockObject
     */
    private $abstractResourceMock;

    /**
     * @var Contacts|MockObject
     */
    private $contactResourceMock;

    /**
     * @var Import&MockObject|MockObject
     */
    private $responseMock;

    /**
     * @var ClientFactory|ClientFactory&MockObject|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var V3ImporterReportHandler|V3ImporterReportHandler&MockObject|MockObject
     */
    private $reportHandlerMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->importerResourceMock = $this->createMock(ImporterResource::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->v3ClientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->importerCollectionMock = $this->createMock(ImporterCollection::class);
        $this->importerModelMock = $this->createMock(Importer::class);
        $this->abstractResourceMock = $this->createMock(AbstractResource::class);
        $this->contactResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->contacts = $this->contactResourceMock;
        $this->responseMock = $this->createMock(Import::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->reportHandlerMock = $this->createMock(V3ImporterReportHandler::class);

        $this->importerCollectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->importerModelMock]));

        $this->v3ProgressHandler = new V3InProgressImportResponseHandler(
            $this->loggerMock,
            $this->clientFactoryMock,
            $this->importerResourceMock,
            $this->reportHandlerMock
        );
    }

    public function testItemsNotFinished()
    {
        $this->clientFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->v3ClientMock);

        $group = [
            'types' => ['Consent'],
            'client' => $this->v3ClientMock,
            'resource' => 'contacts',
            'method' => 'getImportById'
        ];

        $matcher = $this->exactly(2);
        $this->importerModelMock->expects($this->atLeastOnce())
            ->method('__call')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->getInvocationCount()) {
                    1 => ['getWebsiteId'],
                    2 => ['getImportId']
                };
            })
            ->willReturnOnConsecutiveCalls(1, 'import-id');

        $this->contactResourceMock->expects($this->atLeastOnce())
            ->method('getImportById')
            ->willReturn($this->responseMock);

        $itemsCount = $this->v3ProgressHandler->process($group, $this->importerCollectionMock);

        $this->assertEquals($itemsCount, 1);
    }

    public function testItemsFinished()
    {
        $this->clientFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->v3ClientMock);

        $group = [
            'types' => ['Consent'],
            'client' => $this->v3ClientMock,
            'resource' => 'contacts',
            'method' => 'getImportById'
        ];

        $matcher = $this->exactly(3);
        $this->importerModelMock->expects($this->atLeastOnce())
            ->method('__call')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->getInvocationCount()) {
                    1 => ['getWebsiteId'],
                    2 => ['getImportId'],
                    3 => ['setImportStatus'],
                    4 => ['setImportFinished'],
                    5 => ['setMessage'],
                };
            })
            ->willReturnOnConsecutiveCalls(
                1,
                'import-id',
                $this->importerModelMock,
                $this->importerModelMock,
                $this->importerModelMock
            );

        $this->contactResourceMock->expects($this->atLeastOnce())
            ->method('getImportById')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn('Finished');

        $itemsCount = $this->v3ProgressHandler->process($group, $this->importerCollectionMock);
        $this->assertEquals($itemsCount, 0);
    }
}
