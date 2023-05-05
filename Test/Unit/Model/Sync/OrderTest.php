<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection as OrderCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order as OrderResource;
use Dotdigitalgroup\Email\Model\Sync\Order\BatchProcessor;
use Dotdigitalgroup\Email\Model\Sync\Order\Exporter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Model\Sync\Order;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var OrderCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var OrderResourceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderResourceFactoryMock;

    /**
     * @var BatchProcessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $batchProcessorMock;

    /**
     * @var Exporter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $exporterMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManagerInterfaceMock;

    /**
     * @var OrderCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderCollectionMock;

    /**
     * @var OrderResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderResourceMock;

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
        $this->configMock = $this->createMock(Config::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->orderCollectionFactoryMock = $this->createMock(OrderCollectionFactory::class);
        $this->orderResourceFactoryMock = $this->createMock(OrderResourceFactory::class);
        $this->batchProcessorMock = $this->createMock(BatchProcessor::class);
        $this->exporterMock = $this->createMock(Exporter::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerInterfaceMock = $this->createMock(StoreManagerInterface::class);
        $this->orderCollectionMock = $this->createMock(OrderCollection::class);
        $this->orderResourceMock = $this->createMock(OrderResource::class);
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

        $this->order = new Order(
            $this->orderResourceFactoryMock,
            $this->helperMock,
            $this->scopeConfigInterfaceMock,
            $this->batchProcessorMock,
            $this->exporterMock,
            $this->orderCollectionFactoryMock,
            $this->storeManagerInterfaceMock
        );
    }

    public function testOrderSyncIfOrdersAvailable()
    {
        $unexpectedResultMessage = 'Done.';
        $expectedResultMessage = '----------- Order sync ----------- : ' .
            '00:00:00, Total processed = 10, Total synced = 15';

        $this->getLimitAndBreakValue();

        $this->storeManagerInterfaceMock
            ->expects($this->once())
            ->method('getWebsites')
            ->willReturn(
                [$this->websiteInterfaceMock]
            );

        $this->helperMock
            ->expects($this->atLeastOnce())
            ->method('isEnabled')
            ->willReturn(true);

        $this->websiteInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getStoreIds')
            ->willReturn([1,2]);

        $this->orderCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->atLeastOnce())
            ->method('getOrdersToProcess')
            ->willReturnOnConsecutiveCalls($this->getMockOrdersToProcess(), []);

        $this->exporterMock->expects($this->atLeastOnce())
            ->method('exportOrders')
            ->willReturn($this->getMockSyncedOrders());

        $this->orderResourceFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->orderResourceMock);

        $this->orderResourceMock->expects($this->atLeastOnce())
            ->method('setProcessed');

        $this->helperMock->expects($this->atLeastOnce())
            ->method('log');

        $response = $this->order->sync();

        $this->assertNotEquals($response['message'], $unexpectedResultMessage);
        $this->assertEquals($response['message'], $expectedResultMessage);
    }

    /**
     * Tests retrieving the configured sync limit.
     */
    private function getLimitAndBreakValue()
    {
        $this->websiteInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($websiteId = 1);

        $this->scopeConfigInterfaceMock->method('getValue')
            ->withConsecutive(
                [\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT],
                [\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE],
                [\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_ORDERS],
                [
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                    $websiteId
                ]
            )->willReturnOnConsecutiveCalls(1000, null, 5000, 1);
    }

    /**
     * Returns order array
     *
     * @return array
     */
    public function getMockOrdersToProcess()
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
    public function getMockSyncedOrders()
    {
        return [
            '1' => [
                '1' => [],
                '2' => [],
                '3' => [],
                '4' => [],
                '5' => []
            ],
            '2' => [
                '1' => [],
                '2' => [],
                '3' => [],
                '4' => [],
                '5' => []
            ],
            '3' => [
                '1' => [],
                '2' => [],
                '3' => [],
                '4' => [],
                '5' => []
            ],
        ];
    }
}
