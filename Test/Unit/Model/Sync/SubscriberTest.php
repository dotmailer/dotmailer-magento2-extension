<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use ArrayIterator;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Subscriber as SubscriberModel;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MergeManager;
use Dotdigitalgroup\Email\Model\Sync\Export\ExporterInterface;
use Dotdigitalgroup\Email\Model\Sync\Subscriber;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\OrderHistoryChecker;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporter;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporter;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriberTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ContactCollectionFactory|MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var MegaBatchProcessorFactory|MockObject
     */
    private $batchProcessorFactoryMock;

    /**
     * @var MergeManager|MockObject
     */
    private $mergeManagerMock;

    /**
     * @var SubscriberExporterFactory|MockObject
     */
    private $subscriberExporterFactoryMock;

    /**
     * @var SubscriberWithSalesExporterFactory|MockObject
     */
    private $subscriberWithSalesExporterFactoryMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var OrderHistoryChecker|MockObject
     */
    private $orderHistoryCheckerMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var SubscriberExporter|MockObject
     */
    private $subscriberExporterMock;

    /**
     * @var SubscriberWithSalesExporter|MockObject
     */
    private $subscriberWithSalesExporterMock;

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var ContactCollection|ContactCollection&MockObject|MockObject
     */
    private $contactCollectionMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->batchProcessorFactoryMock = $this->createMock(MegaBatchProcessorFactory::class);
        $this->mergeManagerMock = $this->createMock(MergeManager::class);
        $this->orderHistoryCheckerMock = $this->createMock(OrderHistoryChecker::class);
        $this->subscriberExporterFactoryMock = $this->createMock(SubscriberExporterFactory::class);
        $this->subscriberWithSalesExporterFactoryMock = $this->createMock(SubscriberWithSalesExporterFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->subscriberExporterMock = $this->createMock(ExporterInterface::class);
        $this->subscriberWithSalesExporterMock = $this->createMock(ExporterInterface::class);

        $this->websiteInterfaceMock = $this->getMockBuilder(WebsiteInterface::class)
            ->onlyMethods(
                [
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
                ]
            )
            ->addMethods(['getStoreIds', 'getConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new Subscriber(
            $this->helperMock,
            $this->loggerMock,
            $this->contactCollectionFactoryMock,
            $this->batchProcessorFactoryMock,
            $this->mergeManagerMock,
            $this->orderHistoryCheckerMock,
            $this->subscriberExporterFactoryMock,
            $this->subscriberWithSalesExporterFactoryMock,
            $this->scopeConfigMock,
            $this->storeManagerMock
        );
    }

    /**
     * @return void
     * @throws LocalizedException
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

        $this->batchProcessorFactoryMock->expects($this->never())
            ->method('create');

        $this->subscriber->sync();
    }

    /**
     * @return void
     * @throws LocalizedException
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

        $this->subscriberExporterMock->expects($this->never())
            ->method('export');

        $this->subscriber->sync();
    }

    /**
     * @return void
     * @throws LocalizedException
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
            ->willReturn(new ArrayIterator($subscriberStubs));

        $this->subscriberExporterMock->expects($this->once())
            ->method('getFieldMapping')
            ->willReturnOnConsecutiveCalls([], $this->getColumns());

        $this->subscriberExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getSubscribersBatch(5));

        $this->mergeManagerMock->expects($this->once())
            ->method('mergeBatch')
            ->willReturn($this->getSubscribersBatch(5));

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $data = $this->subscriber->sync();

        $this->assertEquals(5, $data['syncedSubscribers']);
    }

    /**
     * @return void
     * @throws LocalizedException
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
            ->willReturn(new ArrayIterator($subscriberStubs));

        $this->subscriberExporterMock->expects($this->once())
            ->method('getFieldMapping')
            ->willReturnOnConsecutiveCalls([], $this->getColumns());

        $this->subscriberExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getSubscribersBatch(5));

        $this->mergeManagerMock->expects($this->once())
            ->method('mergeBatch')
            ->willReturn($this->getSubscribersBatch(5));

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $batchProcessorMock->expects($this->exactly(3))
            ->method('process');

        $data = $this->subscriber->sync();

        $this->assertEquals(5, $data['syncedSubscribers']);
    }

    /**
     * @return void
     * @throws LocalizedException
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
            ->willReturn(new ArrayIterator($subscriberStubs));

        $this->subscriberExporterMock->expects($this->once())
            ->method('getFieldMapping')
            ->willReturn($this->getColumns());

        $this->subscriberExporterMock->expects($this->once())
            ->method('export')
            ->willReturn([]);

        $this->mergeManagerMock->expects($this->never())
            ->method('mergeBatch');

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $this->subscriber->sync();
    }

    /**
     * @return void
     * @throws LocalizedException
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
                new ArrayIterator($this->createCustomerAndGuestSubscriberStubs($limit)),
                new ArrayIterator($this->createCustomerAndGuestSubscriberStubs($limit, $limit + 1))
            );

        $this->loggerMock->expects($this->exactly(3))
            ->method('info');

        $this->subscriberExporterMock->expects($this->exactly(2))
            ->method('getFieldMapping')
            ->willReturnOnConsecutiveCalls([], $this->getColumns());

        $this->subscriberWithSalesExporterMock->expects($this->exactly(2))
            ->method('getFieldMapping')
            ->willReturnOnConsecutiveCalls([], $this->getColumns());

        $this->subscriberExporterMock->expects($this->once())
            ->method('setFieldMapping')
            ->willReturnSelf();

        $this->subscriberWithSalesExporterMock->expects($this->once())
            ->method('setFieldMapping')
            ->willReturnSelf();

        $this->subscriberExporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturnOnConsecutiveCalls(
                $this->getSubscribersBatch(4),
                $this->getSubscribersBatch(0)
            );

        $this->subscriberWithSalesExporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturnOnConsecutiveCalls(
                $this->getSubscribersBatch(2),
                $this->getSubscribersBatch(0)
            );

        $this->mergeManagerMock->expects($this->exactly(2))
            ->method('mergeBatch')
            ->willReturnOnConsecutiveCalls(
                $this->getSubscribersBatch(4),
                $this->getSubscribersBatch(2)
            );

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($batchProcessorMock);

        $batchProcessorMock->expects($this->exactly(2))
            ->method('process');

        $data = $this->subscriber->sync();

        $this->assertEquals(6, $data['syncedSubscribers']);
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
            ->willReturn($this->subscriberExporterMock);
        $this->subscriberWithSalesExporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->subscriberWithSalesExporterMock);
    }

    private function setupSubsWithSalesData()
    {
        $this->orderHistoryCheckerMock->expects($this->exactly(2))
            ->method('checkInSales')
            ->willReturn(
                [
                'chaz2@emailsim.io',
                'chaz6@emailsim.io'
                ]
            );
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
                    'Chaz store',
                    'Chaz store view',
                    'Chaz website',
                    'Subscribed'
                ]
            ];
        }

        return $batch;
    }
}
