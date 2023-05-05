<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Subscriber as SubscriberModel;
use Dotdigitalgroup\Email\Model\Sync\Batch\SubscriberBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Subscriber;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\OrderHistoryChecker;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class SubscriberTest extends TestCase
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
     * @var SubscriberBatchProcessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $batchProcessorMock;

    /**
     * @var AbstractExporter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $abstractExporterMock;

    /**
     * @var SubscriberExporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscriberExporterFactoryMock;

    /**
     * @var SubscriberWithSalesExporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscriberWithSalesExporterFactoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var OrderHistoryChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderHistoryCheckerMock;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManagerMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var ContactCollection|ContactCollection&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject 
     */
    private $contactCollectionMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->batchProcessorMock = $this->createMock(SubscriberBatchProcessor::class);
        $this->orderHistoryCheckerMock = $this->createMock(OrderHistoryChecker::class);
        $this->subscriberExporterFactoryMock = $this->createMock(SubscriberExporterFactory::class);
        $this->subscriberWithSalesExporterFactoryMock = $this->createMock(SubscriberWithSalesExporterFactory::class);
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

        $this->subscriber = new Subscriber(
            $this->helperMock,
            $this->loggerMock,
            $this->contactCollectionFactoryMock,
            $this->batchProcessorMock,
            $this->orderHistoryCheckerMock,
            $this->subscriberExporterFactoryMock,
            $this->subscriberWithSalesExporterFactoryMock,
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
            ->method('getSubscribersToImportByStoreIds');

        $this->subscriberExporterFactoryMock->expects($this->never())
            ->method('create');
        $this->subscriberWithSalesExporterFactoryMock->expects($this->never())
            ->method('create');

        $this->batchProcessorMock->expects($this->never())
            ->method('process');

        $this->subscriber->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testNoExportingIfNoSubscribersNeedSyncing()
    {
        $this->setupForOneEnabledWebsite();
        $this->setupExporters();

        $this->websiteInterfaceMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn([1, 2]);

        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getSubscribersToImportByStoreIds')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $this->abstractExporterMock->expects($this->never())
            ->method('export');

        $this->subscriber->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testExportingStopsIfBreakValueIsExceeded()
    {
        $megaBatchSize = 10;
        $breakValue = 1;
        $isSubscriberSalesDataEnabled = 0;
        $limit = 1;
        $subscriberStubs = $this->createCustomerSubscriberStubs(5);

        $this->setupForOneEnabledWebsite();
        $this->setupExporters();

        $this->websiteInterfaceMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn([1, 2]);

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $isSubscriberSalesDataEnabled, $limit);

        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getSubscribersToImportByStoreIds')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn($subscriberStubs);

        $this->contactCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($subscriberStubs));

        $this->abstractExporterMock->expects($this->once())
            ->method('getCsvFileName')
            ->willReturn($this->getSubscribersFilename());

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('getCsvColumns')
            ->willReturnOnConsecutiveCalls([], $this->getColumns());

        $this->abstractExporterMock->expects($this->once())
            ->method('setCsvColumns');

        $this->abstractExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getSubscribersBatch(5));

        $this->abstractExporterMock->expects($this->once())
            ->method('initialiseCsvFile');

        $this->batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $data = $this->subscriber->sync();

        $this->assertEquals(5, $data['syncedSubscribers']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testBatchIsProcessedOnceMegaBatchSizeIsExceeded()
    {
        $megaBatchSize = 5;
        $breakValue = null;
        $isSubscriberSalesDataEnabled = 0;
        $limit = 5;
        $subscriberStubs = $this->createCustomerSubscriberStubs(5);

        $this->setupForOneEnabledWebsite();
        $this->setupExporters();

        $this->websiteInterfaceMock->expects($this->exactly(2))
            ->method('getStoreIds')
            ->willReturn([1, 2]);

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $isSubscriberSalesDataEnabled, $limit);

        /* Two loops then none remaining */
        $this->contactCollectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getSubscribersToImportByStoreIds')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getItems')
            ->willReturnOnConsecutiveCalls(
                $subscriberStubs,
                []
            );

        $this->contactCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($subscriberStubs));

        $this->abstractExporterMock->expects($this->once())
            ->method('getCsvFileName')
            ->willReturn($this->getSubscribersFilename());

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('getCsvColumns')
            ->willReturnOnConsecutiveCalls([], $this->getColumns());

        $this->abstractExporterMock->expects($this->once())
            ->method('setCsvColumns');

        $this->abstractExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getSubscribersBatch(5));

        $this->abstractExporterMock->expects($this->once())
            ->method('initialiseCsvFile');

        $this->batchProcessorMock->expects($this->exactly(3))
            ->method('process');

        $data = $this->subscriber->sync();

        $this->assertEquals(5, $data['syncedSubscribers']);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testFileNotInitialisedIfNothingToBatch()
    {
        $megaBatchSize = 5;
        $breakValue = null;
        $isSubscriberSalesDataEnabled = 0;
        $limit = 5;
        $subscriberStubs = $this->createCustomerSubscriberStubs(5);

        $this->setupForOneEnabledWebsite();
        $this->setupExporters();

        $this->websiteInterfaceMock->expects($this->exactly(2))
            ->method('getStoreIds')
            ->willReturn([1, 2]);

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $isSubscriberSalesDataEnabled, $limit);

        /* Two loops then none remaining */
        $this->contactCollectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getSubscribersToImportByStoreIds')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getItems')
            ->willReturnOnConsecutiveCalls(
                $subscriberStubs,
                []
            );

        $this->contactCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($subscriberStubs));

        $this->abstractExporterMock->expects($this->once())
            ->method('getCsvFileName')
            ->willReturn($this->getSubscribersFilename());

        $this->abstractExporterMock->expects($this->once())
            ->method('getCsvColumns')
            ->willReturn($this->getColumns());

        $this->abstractExporterMock->expects($this->once())
            ->method('export')
            ->willReturn([]);

        $this->abstractExporterMock->expects($this->never())
            ->method('initialiseCsvFile');

        $this->batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $this->subscriber->sync();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testTwoCohortsAreProcessedWhenSubscribersExportedWithSalesData()
    {
        $megaBatchSize = 100;
        $breakValue = null;
        $isSubscriberSalesDataEnabled = 1;
        $limit = 5;

        $this->setupForOneEnabledWebsite();
        $this->setupExporters();
        $this->setupSubsWithSalesData();

        $this->websiteInterfaceMock->expects($this->exactly(3))
            ->method('getStoreIds')
            ->willReturn([1, 2]);

        /* Set loop limits */
        $this->scopeConfigMock->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, $breakValue, $isSubscriberSalesDataEnabled, $limit);

        /* Two loops then none remaining */
        $this->contactCollectionFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(3))
            ->method('getSubscribersToImportByStoreIds')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->exactly(3))
            ->method('getItems')
            ->willReturnOnConsecutiveCalls(
                $this->createCustomerAndGuestSubscriberStubs($limit),
                $this->createCustomerAndGuestSubscriberStubs($limit, $limit + 1),
                []
            );

        $this->contactCollectionMock->expects($this->exactly(2))
            ->method('getIterator')
            ->willReturnOnConsecutiveCalls(
                new \ArrayIterator($this->createCustomerAndGuestSubscriberStubs($limit)),
                new \ArrayIterator($this->createCustomerAndGuestSubscriberStubs($limit, $limit + 1))
            );

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('getCsvFileName')
            ->willReturn($this->getSubscribersFilename());

        $this->abstractExporterMock->expects($this->exactly(6))
            ->method('getCsvColumns')
            ->willReturnOnConsecutiveCalls(
                [],
                $this->getColumns(),
                [],
                $this->getColumns(),
                $this->getColumns(),
                $this->getColumns()
            );

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('setCsvColumns');

        $this->abstractExporterMock->expects($this->exactly(4))
            ->method('export')
            ->willReturnOnConsecutiveCalls(
                $this->getSubscribersBatch(4),
                $this->getSubscribersBatch(1),
                $this->getSubscribersBatch(4),
                $this->getSubscribersBatch(1)
            );

        $this->abstractExporterMock->expects($this->exactly(2))
            ->method('initialiseCsvFile');

        $this->batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $data = $this->subscriber->sync();

        $this->assertEquals(10, $data['syncedSubscribers']);
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
            ->method('isSubscriberSyncEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('getSubscriberAddressBook')
            ->willReturn('Subscribers');
    }

    /**
     * @return void
     */
    private function setupExporters()
    {
        $this->subscriberExporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->abstractExporterMock);
        $this->subscriberWithSalesExporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->abstractExporterMock);
    }

    private function setupSubsWithSalesData()
    {
        $this->orderHistoryCheckerMock->expects($this->exactly(2))
            ->method('checkInSales')
            ->willReturn([
                'chaz2@emailsim.io',
                'chaz6@emailsim.io'
            ]);
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        return [
            'store_name' => 'STORE_NAME',
            'store_name_additional' => 'STORE_NAME_ADDITIONAL',
            'website_name' => 'WEBSITE_NAME',
            'subscriber_status' => 'SUBSCRIBER_STATUS',
        ];
    }

    /**
     * These are customer subscribers.
     *
     * @return array
     */
    private function createCustomerSubscriberStubs(int $count, int $start = 1)
    {
        $stubs = [];

        for ($i = $start; $i <= $count; $i++) {
            $subStub = $this->getMockBuilder(SubscriberModel::class)
                ->addMethods(['getId', 'getCustomerId', 'getEmail'])
                ->disableOriginalConstructor()
                ->getMock();
            $subStub->method('getId')->willReturn($i);
            $subStub->method('getCustomerId')->willReturn($i);
            $subStub->method('getEmail')->willReturn('chaz'.$i.'@emailsim.io');

            $stubs[] = $subStub;
        }

        return $stubs;
    }

    /**
     * These are customer subscribers.
     *
     * @return array
     */
    private function createCustomerAndGuestSubscriberStubs(int $count, int $start = 1)
    {
        $stubs = [];

        for ($i = $start; $i < ($start + $count); $i++) {
            $subStub = $this->getMockBuilder(SubscriberModel::class)
                ->addMethods(['getId', 'getCustomerId', 'getEmail'])
                ->disableOriginalConstructor()
                ->getMock();
            $subStub->method('getId')->willReturn($i);
            $subStub->method('getCustomerId')->willReturn($i % 2);
            $subStub->method('getEmail')->willReturn('chaz'.$i.'@emailsim.io');

            $stubs[] = $subStub;
        }

        return $stubs;
    }

    /**
     * Some customer data that we might receive back from the exporter.
     *
     * @return array[]
     */
    private function getSubscribersBatch(int $count)
    {
        $batch = [];

        for ($i = 1; $i <= $count; $i++) {
            $batch[] = [
                $i => [
                    'chaz' . $i . '@emailsim.io',
                    'Html',
                    'Chaz store',
                    'Chaz store view',
                    'Chaz website',
                    'Subscribed'
                ]
            ];
        }

        return $batch;
    }

    /**
     * @return string
     */
    private function getSubscribersFilename()
    {
        return 'base_subscribers_25_05_2022_102840_c639d113.csv';
    }

    /**
     * @return string
     */
    private function getSubscribersWithSalesFilename()
    {
        return 'base_subscribers_with_sales_25_05_2022_101744_f8f34b20.csv';
    }
}
