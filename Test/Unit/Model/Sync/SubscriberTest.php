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
use Dotdigitalgroup\Email\Model\Sync\Customer\ListCohortResolver;
use Dotdigitalgroup\Email\Api\Model\Sync\Export\ContactExporterInterface;
use Dotdigitalgroup\Email\Model\Sync\Subscriber;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\OrderHistoryChecker;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporter;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporter;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporterFactory;
use Dotdigitalgroup\Email\Test\Unit\Traits\MockBuilderCompatibilityTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class SubscriberTest extends TestCase
{
    use MockBuilderCompatibilityTrait;

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
     * @var ListCohortResolver|MockObject
     */
    private $listCohortResolverMock;

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
        $this->listCohortResolverMock = $this->createMock(ListCohortResolver::class);

        $this->subscriberExporterMock = $this->createMock(ContactExporterInterface::class);
        $this->subscriberWithSalesExporterMock = $this->createMock(ContactExporterInterface::class);

        $this->websiteInterfaceMock = $this->getMockBuilder($this->classWithAddedMethods(
            WebsiteInterface::class,
            ['getStoreIds', 'getConfig']
        ))
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
                    'setExtensionAttributes',
                    'getStoreIds',
                    'getConfig'
                ]
            )
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
            $this->storeManagerMock,
            $this->listCohortResolverMock
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
     * @throws LocalizedException
     */
    #[DataProvider('sharedListBatchProvider')]
    public function testListCohortsShareWebsiteBatches(
        int $megaBatchSize,
        int $salesDataEnabled,
        int $expectedBatchCount
    ) {
        $this->storeManagerMock->method('getWebsites')->willReturn([$this->websiteInterfaceMock]);
        $this->websiteInterfaceMock->method('getId')->willReturn(1);
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isSubscriberSyncEnabled')->willReturn(true);
        $this->listCohortResolverMock->expects($this->once())
            ->method('resolveSubscribers')
            ->willReturn([$this->getListCohort(100, [1, 2]), $this->getListCohort(200, [3])]);
        $this->setupExporters();

        $this->scopeConfigMock->method('getValue')
            ->willReturnOnConsecutiveCalls($megaBatchSize, null, $salesDataEnabled, 2);

        $this->contactCollectionFactoryMock->method('create')->willReturn($this->contactCollectionMock);

        $pendingSubscribers = $this->createCustomerAndGuestSubscriberStubs(8);
        $currentPage = [];
        $this->contactCollectionMock->method('getSubscribersToImportByStoreIds')
            ->willReturnCallback(function (
                array $storeIds,
                int $limit,
                int $offset
            ) use (
                &$pendingSubscribers,
                &$currentPage
            ) {
                $this->assertGreaterThanOrEqual(0, $offset);
                $currentPage = array_slice(
                    array_filter($pendingSubscribers, function ($subscriber) use ($storeIds) {
                        return $storeIds === [1, 2] ? $subscriber->getId() <= 2 : $subscriber->getId() > 2;
                    }),
                    $offset,
                    $limit
                );
                return $this->contactCollectionMock;
            });
        $this->contactCollectionMock->method('getItems')
            ->willReturnCallback(function () use (&$currentPage) {
                return $currentPage;
            });
        $this->contactCollectionMock->method('getIterator')
            ->willReturnCallback(function () use (&$currentPage) {
                return new ArrayIterator($currentPage);
            });
        $this->orderHistoryCheckerMock->method('checkInSales')->willReturnArgument(0);

        $exportedIds = [];
        $export = function ($contacts, $website, $listId) use (&$exportedIds) {
            $batch = [];
            foreach ($contacts as $id => $email) {
                $exportedIds[] = $id;
                $this->assertSame($id <= 2 ? 100 : 200, $listId);
                if ($id === 3) {
                    continue;
                }
                $batch[$id] = ['email' => $email, 'listId' => $listId];
            }
            return $batch;
        };
        foreach ([$this->subscriberExporterMock, $this->subscriberWithSalesExporterMock] as $exporter) {
            $exporter->method('getFieldMapping')->willReturn($this->getColumns());
            $exporter->method('export')->willReturnCallback($export);
        }
        $this->mergeManagerMock->method('mergeBatch')
            ->willReturnCallback([new MergeManager(), 'mergeBatch']);

        $batchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->batchProcessorFactoryMock->method('create')->willReturn($batchProcessorMock);
        $processedBatches = [];
        $batchProcessorMock->method('process')
            ->willReturnCallback(function (
                array $batch,
                int $websiteId
            ) use (
                &$pendingSubscribers,
                &$processedBatches
            ) {
                $this->assertSame(1, $websiteId);
                if (!$batch) {
                    return;
                }
                $processedBatches[] = $batch;
                $pendingSubscribers = array_filter($pendingSubscribers, function ($subscriber) use ($batch) {
                    return !isset($batch[$subscriber->getId()]);
                });
            });
        $this->loggerMock->expects($this->never())->method('error');

        $data = $this->subscriber->sync();

        sort($exportedIds);
        $this->assertSame(range(1, 8), $exportedIds);
        $this->assertSame(7, $data['syncedSubscribers']);
        $this->assertCount($expectedBatchCount, $processedBatches);
        $this->assertSame([100, 200], array_values(array_unique(array_column($processedBatches[0], 'listId'))));
        if ($salesDataEnabled) {
            foreach ($processedBatches as $batch) {
                $this->assertCount(1, array_unique(array_map(function (int $id) {
                    return $id % 2;
                }, array_keys($batch))));
            }
        }
        $this->assertSame([3], array_values(array_map(function ($subscriber) {
            return $subscriber->getId();
        }, $pendingSubscribers)));
    }

    public static function sharedListBatchProvider(): array
    {
        return [
            'website remainder' => [100, 0, 1],
            'mixed-list threshold' => [3, 0, 2],
            'sales website remainders' => [100, 1, 2],
            'sales mixed-list thresholds' => [3, 1, 3]
        ];
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

        $this->listCohortResolverMock->expects($this->once())
            ->method('resolveSubscribers')
            ->with($this->websiteInterfaceMock)
            ->willReturn([$this->getListCohort(100, [1, 2])]);
    }

    /**
     * @param int $listId
     * @param array $storeIds
     *
     * @return array
     */
    private function getListCohort(int $listId, array $storeIds): array
    {
        return [
            'listId' => $listId,
            'onlySubscribers' => false,
            'storeIds' => $storeIds,
            'includeUnassigned' => false
        ];
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
            $subStub = $this->getMockBuilder($this->classWithAddedMethods(
                SubscriberModel::class,
                ['getId', 'getCustomerId', 'getEmail']
            ))
                ->onlyMethods(['getId', 'getCustomerId', 'getEmail'])
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
            $subStub = $this->getMockBuilder($this->classWithAddedMethods(
                SubscriberModel::class,
                ['getId', 'getCustomerId', 'getEmail']
            ))
                ->onlyMethods(['getId', 'getCustomerId', 'getEmail'])
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
