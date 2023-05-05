<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Importer;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Cron\CronOffsetter;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory as ImporterCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\Serialize\SerializerInterface;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterReportHandler;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class ImporterReportHandlerTest extends TestCase
{
    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientMock;

    /**
     * @var ImporterCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerCollectionFactoryMock;

    /**
     * @var ImporterCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerCollectionMock;

    /**
     * @var DataObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataObjectMockOne;

    /**
     * @var DataObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataObjectMockTwo;

    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializerMock;

    /**
     * @var ImporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerFactoryMock;

    /**
     * @var Importer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerMock;

    /**
     * @var ImporterReportHandler
     */
    private $importerReportHandler;

    /**
     * @var CronOffsetter|CronOffsetter&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cronOffsetterMock;

    /**
     * @var Contact|Contact&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactResourceMock;

    /**
     * @var ContactCollectionFactory|ContactCollectionFactory&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var ScopeConfigInterface|ScopeConfigInterface&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var DateTime|DateTime&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dateTimeMock;

    /**
     * @var Logger|Logger&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->cronOffsetterMock = $this->createMock(CronOffsetter::class);
        $this->importerFactoryMock = $this->createMock(ImporterFactory::class);
        $this->contactResourceMock = $this->createMock(Contact::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->importerCollectionFactoryMock = $this->createMock(ImporterCollectionFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->importerCollectionMock = $this->createMock(ImporterCollection::class);
        $this->dataObjectMockOne = $this->createMock(DataObject::class);
        $this->dataObjectMockTwo = $this->createMock(DataObject::class);
        $this->importerMock = $this->createMock(Importer::class);

        $this->importerReportHandler = new ImporterReportHandler(
            $this->scopeConfigInterfaceMock,
            $this->importerFactoryMock,
            $this->contactCollectionFactoryMock,
            $this->contactResourceMock,
            $this->dateTimeMock,
            $this->cronOffsetterMock,
            $this->importerCollectionFactoryMock,
            $this->serializerMock,
            $this->loggerMock
        );
    }

    public function testInsightReportFaults()
    {
        $importId = 'chaz-import-id';

        $this->clientMock->expects($this->once())
            ->method('getTransactionalDataReportById')
            ->willReturn($this->getTransactionalReport());

        $this->importerCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->importerCollectionMock);

        $this->importerCollectionMock->expects($this->once())
            ->method('getImporterDataByImportId')
            ->with($importId)
            ->willReturn($this->importerCollectionMock);

        $this->importerCollectionMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnOnConsecutiveCalls(
                $encodedImportedData = $this->getImportedData(),
                $attempt = 0
            );

        $this->serializerMock->expects($this->atLeastOnce())
            ->method('unserialize')
            ->willReturn($decodedImportedData = (array) json_decode($encodedImportedData, true));

        $this->importerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->importerMock);

        $this->importerMock->expects($this->once())
            ->method('registerQueue');

        $this->importerReportHandler->processInsightReportFaults(
            $importId,
            $websiteId = 1,
            $this->clientMock,
            $importType = 'Orders'
        );
    }

    /**
     *
     * @return object
     */
    private function getTransactionalReport()
    {
        return (object) [
           'totalItems' => 4,
           'totalImported' => 2,
           'totalRejected' => 2,
           'faults' => [
               (object) [
                   'key' => "000000159",
                   'reason' => "ContactEmailDoesNotExist",
                   'detail' => "chaz-kangaroo@emailsim.io"
               ],
               (object) [
                   'key' => "000000160",
                   'reason' => "ContactEmailDoesNotExist",
                   'detail' => "chaz-kangaroo-2@emailsim.io"
               ]
           ]
        ];
    }

    /**
     * @return string
     */
    private function getImportedData()
    {
        return
            '{"000000159":{"id":"000000159","email":"chaz-kangaroo@emailsim.io"},
            "000000160":{"id":"000000160","email":"chaz-kangaroo-2@emailsim.io"},
            "000000161":{"id":"000000161","email":"order-delay-test@emailsim.io"},
            "000000162":{"id":"000000162","email":"testorder@emailsim.com"}}'
        ;
    }
}
