<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use DateTime;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\BatchMergerInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order as OrderResource;
use Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Order\Exporter;
use Dotdigitalgroup\Email\Model\Sync\Order\ExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerInterfaceMock;

    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var ExporterFactory|MockObject
     */
    private $exporterFactoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var MegaBatchProcessorFactory|MockObject
     */
    private $megaBatchProcessorFactoryMock;

    /**
     * @var OrderResourceFactory|MockObject
     */
    private $orderResourceFactoryMock;

    /**
     * @var OrderCollectionFactory|MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var BatchMergerInterface|MockObject
     */
    private $mergeManagerMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var Order
     */
    private $order;

    protected function setUp(): void
    {
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerInterfaceMock = $this->createMock(StoreManagerInterface::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->exporterFactoryMock = $this->createMock(ExporterFactory::class);
        $this->megaBatchProcessorFactoryMock = $this->createMock(MegaBatchProcessorFactory::class);
        $this->orderResourceFactoryMock = $this->createMock(OrderResourceFactory::class);
        $this->orderCollectionFactoryMock = $this->createMock(OrderCollectionFactory::class);
        $this->mergeManagerMock = $this->createMock(BatchMergerInterface::class);
        $this->loggerMock = $this->createMock(Logger::class);
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
            ->addMethods(['getStoreIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = new Order(
            $this->scopeConfigInterfaceMock,
            $this->storeManagerInterfaceMock,
            $this->helperMock,
            $this->exporterFactoryMock,
            $this->megaBatchProcessorFactoryMock,
            $this->orderResourceFactoryMock,
            $this->orderCollectionFactoryMock,
            $this->mergeManagerMock,
            $this->loggerMock
        );
    }

    public function testOrderSyncIfOrdersAvailable()
    {
        $this->getLimitAndBreakValue(1000, 4000);

        $this->storeManagerInterfaceMock
            ->expects($this->once())
            ->method('getWebsites')
            ->willReturn(
                [$this->websiteInterfaceMock]
            );

        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isOrderSyncEnabled')->willReturn(true);

        $orderCollectionMock = $this->createMock(Collection::class);
        $this->orderCollectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects($this->exactly(2))
            ->method('getOrdersToProcess')
            ->willReturnOnConsecutiveCalls($this->getMockOrdersToProcess(), []);

        $exporterMock = $this->createMock(Exporter::class);
        $this->exporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($exporterMock);

        $exporterMock->expects($this->atLeastOnce())
            ->method('export')
            ->willReturn($this->getMockOrderBatch(10));

        $this->mergeManagerMock->expects($this->once())
            ->method('mergeBatch')
            ->willReturn($this->getMockOrderBatch(10));

        $orderResourceMock = $this->createMock(OrderResource::class);
        $this->orderResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($orderResourceMock);

        $orderResourceMock->expects($this->once())
            ->method('setProcessed');

        $megaBatchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->megaBatchProcessorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($megaBatchProcessorMock);

        $megaBatchProcessorMock->expects($this->once())
            ->method('process');

        $result = $this->order->sync(new DateTime());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('syncedOrders', $result);
    }

    public function testStopLoopingIfBreakValueReached()
    {
        $this->getLimitAndBreakValue(1000, 4000, 10);

        $this->storeManagerInterfaceMock
            ->expects($this->once())
            ->method('getWebsites')
            ->willReturn(
                [$this->websiteInterfaceMock]
            );

        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isOrderSyncEnabled')->willReturn(true);

        $orderCollectionMock = $this->createMock(Collection::class);
        $this->orderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects($this->once())
            ->method('getOrdersToProcess')
            ->willReturnOnConsecutiveCalls($this->getMockOrdersToProcess(), []);

        $exporterMock = $this->createMock(Exporter::class);
        $this->exporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($exporterMock);

        $exporterMock->expects($this->atLeastOnce())
            ->method('export')
            ->willReturn($this->getMockOrderBatch(10));

        $this->mergeManagerMock->expects($this->once())
            ->method('mergeBatch')
            ->willReturn($this->getMockOrderBatch(10));

        $orderResourceMock = $this->createMock(OrderResource::class);
        $this->orderResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($orderResourceMock);

        $orderResourceMock->expects($this->once())
            ->method('setProcessed');

        $megaBatchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->megaBatchProcessorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($megaBatchProcessorMock);

        $megaBatchProcessorMock->expects($this->once())
            ->method('process');

        $this->order->sync(new DateTime());
    }

    public function testMultipleWebsitesStopAtBreakValue()
    {
        $this->getScopeConfigsInTwoWebsiteLoop(10, 400, 20);

        $this->storeManagerInterfaceMock
            ->expects($this->once())
            ->method('getWebsites')
            ->willReturn(
                [$this->websiteInterfaceMock, $this->websiteInterfaceMock]
            );

        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isOrderSyncEnabled')->willReturn(true);

        $orderCollectionMock = $this->createMock(Collection::class);
        $this->orderCollectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects($this->exactly(2))
            ->method('getOrdersToProcess')
            ->willReturnOnConsecutiveCalls(
                $this->getMockOrdersToProcess(),
                $this->getMockOrdersToProcess()
            );

        $exporterMock = $this->createMock(Exporter::class);
        $this->exporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($exporterMock);

        $exporterMock->expects($this->exactly(2))
            ->method('export')
            ->willReturn($this->getMockOrderBatch(10));

        $this->mergeManagerMock->expects($this->exactly(2))
            ->method('mergeBatch')
            ->willReturnOnConsecutiveCalls(
                $this->getMockOrderBatch(10),
                $this->getMockOrderBatch(20)
            );

        $orderResourceMock = $this->createMock(OrderResource::class);
        $this->orderResourceFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($orderResourceMock);

        $orderResourceMock->expects($this->exactly(2))
            ->method('setProcessed');

        $megaBatchProcessorMock = $this->createMock(MegaBatchProcessor::class);
        $this->megaBatchProcessorFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($megaBatchProcessorMock);

        $megaBatchProcessorMock->expects($this->once())
            ->method('process');

        $this->order->sync(new DateTime());
    }

    /**
     * Tests retrieving the configured sync limit.
     */
    private function getLimitAndBreakValue(int $limit, int $megaBatchSize, ?int $breakValue = null)
    {
        $matcher = $this->exactly(3);
        $this->scopeConfigInterfaceMock
            ->expects($matcher)
            ->method('getValue')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT],
                    2 => [Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_ORDERS],
                    3 => [Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE],
                };
            })
            ->willReturnOnConsecutiveCalls($limit, $megaBatchSize, $breakValue);
    }

    private function getScopeConfigsInTwoWebsiteLoop(int $limit, int $megaBatchSize, ?int $breakValue = null)
    {
        $matcher = $this->exactly(6);
        $this->scopeConfigInterfaceMock
            ->expects($matcher)
            ->method('getValue')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT],
                    2 => [Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_ORDERS],
                    3 => [Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE],
                    4 => [Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT],
                    5 => [Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_ORDERS],
                    6 => [Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE],
                };
            })
            ->willReturnOnConsecutiveCalls(
                $limit,
                $megaBatchSize,
                $breakValue,
                $limit,
                $megaBatchSize,
                $breakValue
            );
    }

    /**
     * Returns order array
     *
     * @return array
     */
    private function getMockOrdersToProcess()
    {
        return [
            0 => '1205',
            1 => '1206',
            2 => '1207',
            3 => '1208',
            4 => '1209',
            5 => '1210',
            6 => '1211',
            7 => '1212',
            8 => '1213',
            9 => '1214'
        ];
    }

    /**
     * Returns order array
     *
     * @return array
     */
    private function getMockOrderBatch(int $count)
    {
        $batch = [];
        for ($i = 1; $i <= $count; $i++) {
            $batch[$i] = [
                'email' => 'test' . $i . '@example.com',
                'type' => 'Html',
                'id' => $i,
                'quote_id' => 0,
                'store_id' => null,
                'grand_total' => 0.0,
                'firstname' => 'Test',
                'lastname' => 'User'
            ];
        }

        return $batch;
    }
}
