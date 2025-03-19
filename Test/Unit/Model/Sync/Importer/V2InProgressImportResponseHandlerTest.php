<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterProgressHandler;
use Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler\V2ImporterReportHandler;
use Dotdigitalgroup\Email\Model\Sync\Importer\V2InProgressImportResponseHandler;
use Dotdigitalgroup\Email\Model\Sync\Importer\V2InProgressImportResponseHandlerFactory as V2HandlerFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use PHPUnit\Framework\TestCase;

class V2InProgressImportResponseHandlerTest extends TestCase
{
    /**
     * @var Data|Data&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var File|File&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileMock;

    /**
     * @var Logger|Logger&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ImporterResource|ImporterResource&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerResourceMock;

    /**
     * @var V2ImporterReportHandler|V2ImporterReportHandler&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $v2ReportHandler;

    /**
     * @var V2InProgressImportResponseHandler
     */
    private $v2ResponseHandler;

    /**
     * @var V2HandlerFactory|V2HandlerFactory&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $v2HandlerFactoryMock;

    /**
     * @var ImporterCollection|ImporterCollection&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerCollectionMock;

    /**
     * @var ImporterModel&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerModelMock;

    /**
     * @var Client|Client&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->importerResourceMock = $this->createMock(ImporterResource::class);
        $this->v2HandlerFactoryMock = $this->createMock(V2HandlerFactory::class);
        $this->v2ReportHandler = $this->createMock(V2ImporterReportHandler::class);

        $this->clientMock = $this->createMock(Client::class);
        $this->importerModelMock = $this->createMock(Importer::class);

        $this->importerCollectionMock = $this->createMock(ImporterCollection::class);

        $this->importerCollectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->importerModelMock]));

        $this->helperMock->expects($this->atLeastOnce())
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->v2ResponseHandler = new V2InProgressImportResponseHandler(
            $this->helperMock,
            $this->importerResourceMock,
            $this->v2ReportHandler,
            $this->fileMock,
            $this->loggerMock
        );
    }

    public function testFinishedInsightDataItems()
    {
        $groups = [
            ImporterProgressHandler::PROGRESS_GROUP_MODEL => $this->v2HandlerFactoryMock,
            ImporterProgressHandler::PROGRESS_GROUP_METHOD => 'getContactsTransactionalDataImportByImportId',
            ImporterProgressHandler::PROGRESS_GROUP_TYPES => [
                ImporterModel::IMPORT_TYPE_ORDERS,
                ImporterModel::IMPORT_TYPE_REVIEWS,
                ImporterModel::IMPORT_TYPE_WISHLIST,
                'Catalog'
            ]
        ];

        $this->clientMock->expects($this->atLeastOnce())
            ->method('getContactsTransactionalDataImportByImportId')
            ->willReturn((object) ['status' => 'Finished']);

        $matcher = $this->exactly(12);
        $this->importerModelMock->expects($this->atLeastOnce())
            ->method('__call')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->getInvocationCount()) {
                    1 => ['getWebsiteId'],
                    2 => ['getImportId'],
                    3 => ['setImportStatus'],
                    4 => ['setImportFinished'],
                    5 => ['setMessage'],
                    6 => ['getImportType'],
                    7 => ['getImportId'],
                    8 => ['getImportId'],
                    9 => ['getWebsiteId'],
                    10 => ['getWebsiteId'],
                    11 => ['getImportType'],
                    12 => ['getWebsiteId'],
                };
            })
            ->willReturnOnConsecutiveCalls(
                1,
                'import-id',
                $this->importerModelMock,
                $this->importerModelMock,
                $this->importerModelMock,
                ImporterModel::IMPORT_TYPE_ORDERS,
                'import-id',
                'import-id',
                1,
                1,
                ImporterModel::IMPORT_TYPE_CONTACT,
                1
            );

        $this->v2ReportHandler->expects($this->atLeastOnce())
            ->method('processInsightReportFaults');

        $inProgress = $this->v2ResponseHandler->process($groups, $this->importerCollectionMock);

        $this->assertEquals($inProgress, 0);
    }

    public function testFinishedContactDataItems()
    {
        $groups = [
            ImporterProgressHandler::PROGRESS_GROUP_TYPES => [
                ImporterModel::IMPORT_TYPE_CONTACT,
                ImporterModel::IMPORT_TYPE_CUSTOMER,
                ImporterModel::IMPORT_TYPE_GUEST,
                ImporterModel::IMPORT_TYPE_SUBSCRIBERS,
            ],
            ImporterProgressHandler::PROGRESS_GROUP_MODEL => $this->v2HandlerFactoryMock,
            ImporterProgressHandler::PROGRESS_GROUP_METHOD => 'getContactsImportByImportId'
        ];

        $this->clientMock->expects($this->atLeastOnce())
            ->method('getContactsImportByImportId')
            ->willReturn((object) ['status' => 'Finished']);

        $matcher = $this->exactly(12);
        $this->importerModelMock->expects($this->atLeastOnce())
            ->method('__call')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->getInvocationCount()) {
                    1 => ['getWebsiteId'],
                    2 => ['getImportId'],
                    3 => ['setImportStatus'],
                    4 => ['setImportFinished'],
                    5 => ['setMessage'],
                    6 => ['getImportType'],
                    7 => ['getImportFile'],
                    8 => ['getImportId'],
                    9 => ['getImportId'],
                    10 => ['getWebsiteId'],
                    11 => ['getImportType'],
                    12 => ['getWebsiteId'],
                };
            })
            ->willReturnOnConsecutiveCalls(
                1,
                'import-id',
                $this->importerModelMock,
                $this->importerModelMock,
                $this->importerModelMock,
                ImporterModel::IMPORT_TYPE_CONTACT,
                'customers.csv',
                'import-id',
                'import-id',
                1,
                ImporterModel::IMPORT_TYPE_CONTACT,
                1
            );

        $this->fileMock->expects($this->atLeastOnce())
            ->method('isFilePathExistWithFallback')
            ->willReturn(true);

        $this->fileMock->expects($this->atLeastOnce())
            ->method('isFileAlreadyArchived')
            ->willReturn(false);

        $this->fileMock->expects($this->atLeastOnce())
            ->method('archiveCSV');

        $this->v2ReportHandler->expects($this->atLeastOnce())
            ->method('processContactImportReportFaults');

        $inProgress = $this->v2ResponseHandler->process($groups, $this->importerCollectionMock);

        $this->assertEquals($inProgress, 0);
    }
}
