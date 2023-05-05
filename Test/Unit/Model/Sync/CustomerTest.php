<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\CustomerBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Customer;
use Dotdigitalgroup\Email\Model\Sync\Customer\ExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ContactCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var CustomerBatchProcessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $batchProcessorMock;

    /**
     * @var AbstractExporter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $abstractExporterMock;

    /**
     * @var ExporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $exporterFactoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManagerMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var ContactCollection|ContactCollection&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionMock;

    protected function setUp(): void {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->batchProcessorMock = $this->createMock(CustomerBatchProcessor::class);
        $this->exporterFactoryMock = $this->createMock(ExporterFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->abstractExporterMock = $this->createMock(AbstractExporter::class);
        $this->websiteInterfaceMock = $this->getMockBuilder(WebsiteInterface::class)
            ->onlyMethods([
                'getId',
                'setId',
                'getCode',
                'setCode',
                'getName',
                'setName',
                'getDefaultGroupId',
                'setDefaultGroupId',
                'getExtensionAttributes',
                'setExtensionAttributes'
            ])
            ->addMethods(['getStoreIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteInterfaceMock
            ->method('getId')
            ->willReturn(1);

        $this->customer = new Customer(
            $this->helperMock,
            $this->loggerMock,
            $this->contactCollectionFactoryMock,
            $this->batchProcessorMock,
            $this->exporterFactoryMock,
            $this->scopeConfigMock,
            $this->storeManagerMock
        );
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testNoExportingOrBatchingIfSyncIsNotConfigured()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$this->websiteInterfaceMock]);

        $this->contactCollectionFactoryMock->expects($this->never())
            ->method('create');

        $this->contactCollectionMock->expects($this->never())
            ->method('getCustomersToImportByWebsite');

        $this->exporterFactoryMock->expects($this->never())
            ->method('create');

        $this->batchProcessorMock->expects($this->never())
            ->method('process');

        $this->customer->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testNoExportingIfNoCustomersNeedSyncing()
    {
        $this->setupForOneEnabledWebsite();
        $this->setUpColumns();

        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getCustomersToImportByWebsite')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getColumnValues')
            ->willReturn([]);

        $this->abstractExporterMock->expects($this->never())
            ->method('export');

        $this->customer->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testExportingStopsIfBreakValueIsExceeded()
    {
        $megaBatchSize = 10;
        $limit = 1;
        $breakValue = 1;

        $this->setupForOneEnabledWebsite();
        $this->setUpColumns();

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $limit);

        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getCustomersToImportByWebsite')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getColumnValues')
            ->willReturn([1, 21, 309]);

        $this->abstractExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getCustomersBatch());

        $this->abstractExporterMock->expects($this->once())
            ->method('getCsvColumns')
            ->willReturn($this->getColumns());

        $this->abstractExporterMock->expects($this->once())
            ->method('initialiseCsvFile')
            ->willReturn($this->getFilename());

        $data = $this->customer->sync();

        $this->assertEquals(3, $data['syncedCustomers']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testBatchIsProcessedOnceMegaBatchSizeIsExceeded()
    {
        $megaBatchSize = 5;
        $limit = 5;
        $breakValue = null;

        $this->setupForOneEnabledWebsite();
        $this->setUpColumns();

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $limit);

        /* Two loops then none remaining */
        $this->contactCollectionFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(3))
            ->method('getCustomersToImportByWebsite')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(3))
            ->method('getColumnValues')
            ->willReturnOnConsecutiveCalls(
                [1, 21, 309],
                [321, 322, 323],
                []
            );

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturnOnConsecutiveCalls(
                $this->getCustomersBatch(),
                $this->getCustomersBatchTwo()
            );

        $this->abstractExporterMock->expects($this->once())
            ->method('getCsvColumns')
            ->willReturn($this->getColumns());

        $this->abstractExporterMock->expects($this->once())
            ->method('initialiseCsvFile')
            ->willReturn($this->getFilename());

        $this->batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $data = $this->customer->sync();

        $this->assertEquals(6, $data['syncedCustomers']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testFileNotInitialisedIfNothingToBatch()
    {
        $this->setupForOneEnabledWebsite();
        $this->setUpColumns();

        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getCustomersToImportByWebsite')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getColumnValues')
            ->willReturn([1, 2, 3, 4, 5]);

        $this->abstractExporterMock->expects($this->once())
            ->method('export')
            ->willReturn([]);

        $this->abstractExporterMock->expects($this->never())
            ->method('initialiseCsvFile');

        $this->customer->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testCustomersAreExportedAndBatchedForTwoWebsites()
    {
        $megaBatchSize = 10;
        $limit = 5;
        $breakValue = null;

        $this->setupForTwoEnabledWebsites();

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $megaBatchSize,
                $breakValue,
                $limit,
                $limit
            );

        $this->exporterFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->abstractExporterMock);

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('setCsvColumns');

        /* Two loops for each website (one with some customers, then none remaining) */
        $this->contactCollectionFactoryMock->expects($this->exactly(4))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(4))
            ->method('getCustomersToImportByWebsite')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(4))
            ->method('getColumnValues')
            ->willReturnOnConsecutiveCalls(
                [1, 2, 3],
                [],
                [6, 7, 8],
                []
            );

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturnOnConsecutiveCalls(
                $this->getCustomersBatch(),
                $this->getCustomersBatchTwo()
            );

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('getCsvColumns')
            ->willReturn($this->getColumns());

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('initialiseCsvFile')
            ->willReturn($this->getFilename());

        $this->batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $data = $this->customer->sync();

        $this->assertEquals(6, $data['syncedCustomers']);
    }

    /**
     * Reflects the edge-case where we have duplicate contacts in our table.
     *
     * The customerIdCount is 10 and we use this for the offset on the next query
     * BUT the mega batch size is only 3 (i.e. 3 distinct ids) therefore the total
     * synced is also only 3.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testCustomersCountIfDuplicateIdsInTable()
    {
        $megaBatchSize = 10;
        $limit = 10;
        $breakValue = null;

        $this->setupForOneEnabledWebsite();
        $this->setUpColumns();

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $limit);

        $this->contactCollectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getCustomersToImportByWebsite')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getColumnValues')
            ->willReturnOnConsecutiveCalls(
                [1, 1, 1, 21, 21, 21, 21, 309, 309, 309],
                []
            );

        $this->abstractExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getCustomersBatch());

        $this->abstractExporterMock->expects($this->once())
            ->method('getCsvColumns')
            ->willReturn($this->getColumns());

        $this->abstractExporterMock->expects($this->once())
            ->method('initialiseCsvFile')
            ->willReturn($this->getFilename());

        $data = $this->customer->sync();

        $this->assertEquals(3, $data['syncedCustomers']);
    }

    /**
     * @return void
     */
    private function setupForOneEnabledWebsite()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$this->websiteInterfaceMock]);

        $this->helperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn('Customers');
    }

    /**
     * @return void
     */
    private function setupForTwoEnabledWebsites()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([
                $this->websiteInterfaceMock,
                $this->websiteInterfaceMock
            ]);

        $this->helperMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->exactly(2))
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->exactly(2))
            ->method('getCustomerAddressBook')
            ->willReturn('Customers');
    }

    /**
     * @return void
     */
    private function setupColumns()
    {
        $this->exporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->abstractExporterMock);

        $this->abstractExporterMock->expects($this->once())
            ->method('setCsvColumns');
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
     * Some more customers.
     *
     * @return array[]
     */
    private function getCustomersBatchTwo()
    {
        return [
            321 => ['chazco@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chaz', 'Kangaroo'],
            322 => ['chaz2@emailsim.io', 'Html', '1', 0, null, 0.0, 'Dave', 'Dot'],
            323 => ['chaz3@emailsim.io', 'Html', '1', 0, null, 0.0, 'Chip', 'Chop'],
        ];
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        $datafield = new Datafield();
        $columns = [];

        foreach ($datafield->getContactDatafields() as $key => $properties) {
            $columns[$key] = $properties['name'];
        }

        return $columns;
    }

    /**
     * @return string
     */
    private function getFilename()
    {
        return 'base_customer_25_05_2022_130304_85681d6a.csv';
    }
}
