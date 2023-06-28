<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigital\Resources\AbstractResource;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\Sync\Importer\V3InProgressImportResponseHandler;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler\V3ImporterReportHandler;
use Dotdigital\V3\Models\Contact\Import;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use PHPUnit\Framework\TestCase;

class V3InProgressImportResponseHandlerTest extends TestCase
{
    /**
     * @var ImporterResource|ImporterResource&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerResourceMock;

    /**
     * @var Logger|Logger&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var V3InProgressImportResponseHandlerFactory
     */
    private $v3ProgressHandlerFactory;

    /**
     * @var Client|Client&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $v3ClientMock;

    /**
     * @var ImporterCollection|ImporterCollection&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerCollectionMock;

    /**
     * @var Importer|Importer&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerModelMock;

    /**
     * @var AbstractResource&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $abstractResourceMock;

    /**
     * @var Import&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseMock;

    /**
     * @var ClientFactory|ClientFactory&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientFactoryMock;

    /**
     * @var V3ImporterReportHandler|V3ImporterReportHandler&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
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
        $this->importerModelMock = $this->getMockBuilder(Importer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getImportId','getWebsiteId'])
            ->getMock();

        $this->abstractResourceMock = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getImportById'])
            ->getMock();

        $this->v3ClientMock->contacts = $this->abstractResourceMock;

        $this->responseMock = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatus'])
            ->getMock();

        $this->clientFactoryMock = $this->createMock(ClientFactory::class);

        $this->reportHandlerMock = $this->createMock(V3ImporterReportHandler::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);

        //@codingStandardsIgnoreStart
        $this->importerCollectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayObject([$this->importerModelMock]));
        //@codingStandardsIgnoreEnd

        $this->importerModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->v3ProgressHandlerFactory = new V3InProgressImportResponseHandler(
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

        $this->importerModelMock->expects($this->atLeastOnce())
            ->method('getImportId')
            ->willReturn('import-id');

        $this->abstractResourceMock->expects($this->atLeastOnce())
            ->method('getImportById')
            ->with('import-id')
            ->willReturn($this->responseMock);

        $itemsCount = $this->v3ProgressHandlerFactory->process($group, $this->importerCollectionMock);

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

        $this->importerModelMock->expects($this->atLeastOnce())
            ->method('getImportId')
            ->willReturn('import-id');

        $this->abstractResourceMock->expects($this->atLeastOnce())
            ->method('getImportById')
            ->with('import-id')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn('Finished');

        $itemsCount = $this->v3ProgressHandlerFactory->process($group, $this->importerCollectionMock);
        $this->assertEquals($itemsCount, 0);
    }
}
