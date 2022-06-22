<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Order;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\OrderFactory as ConnectorOrderFactory;
use Dotdigitalgroup\Email\Model\Connector\Order as ConnectorOrder;
use Dotdigitalgroup\Email\Model\OrderFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection as OrderCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as SalesOrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Dotdigitalgroup\Email\Model\Sync\Order\Exporter;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
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
     * @var ConnectorOrderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectorOrderFactory;

    /**
     * @var OrderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderFactoryMock;

    /**
     * @var OrderCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderCollectionMock;

    /**
     * @var OrderCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var SalesOrderCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $salesOrderCollectionMock;

    /**
     * @var SalesOrderCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $salesOrderCollectionFactoryMock;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManagerInterfaceMock;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var ConnectorOrder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectorOrderMock;

    /**
     * @var StoreInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeInterfaceMock;

    /**
     * @var OrderCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderCollection;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->connectorOrderFactory = $this->createMock(ConnectorOrderFactory::class);
        $this->connectorOrderMock = $this->createMock(ConnectorOrder::class);

        $this->orderFactoryMock = $this->createMock(OrderFactory::class);
        $this->orderCollectionMock = $this->getMockBuilder(OrderCollection::class)
            ->onlyMethods(['getOrdersFromIds'])
            ->addMethods(['getId','getOrderStatus', 'getStoreId', 'getStore', 'getIncrementId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeInterfaceMock = $this->createMock(StoreInterface::class);
        $this->orderCollectionFactoryMock = $this->createMock(OrderCollectionFactory::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->salesOrderCollectionMock = $this->createMock(SalesOrderCollection::class);
        $this->salesOrderCollectionFactoryMock = $this->createMock(SalesOrderCollectionFactory::class);
        $this->storeManagerInterfaceMock = $this->createMock(StoreManagerInterface::class);
        $this->orderCollection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        $this->exporter = new Exporter(
            $this->storeManagerInterfaceMock,
            $this->scopeConfigInterfaceMock,
            $this->connectorOrderFactory,
            $this->orderCollectionFactoryMock,
            $this->loggerMock,
            $this->salesOrderCollectionFactoryMock
        );
    }

    public function testExportOrdersWillReturnEmptyArrayIfNoOrdersFound()
    {
        $iterator = new \ArrayIterator([]);

        $this->orderCollection->expects($this->any())->method('getIterator')->will($this->returnValue($iterator));

        $this->fetchMockedOrdersFromIds();

        $result = $this->exporter->exportOrders(['1','2','3']);

        $this->assertEmpty($result);
    }

    public function testExportOrdersIfOrdersFound()
    {

        $iterator = new \ArrayIterator([$this->orderCollectionMock]);

        $this->orderCollection
            ->expects($this->atLeastOnce())
            ->method('getIterator')
            ->will($this->returnValue($iterator));

        $this->orderCollection
            ->expects($this->atLeastOnce())
            ->method('getIterator')
            ->will($this->returnValue($iterator));

        $this->fetchMockedOrdersFromIds();

        $this->orderCollection->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->orderCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getIncrementId')
            ->willReturn(1);

        $this->orderCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn(1);

        $this->storeManagerInterfaceMock
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->scopeConfigInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn('pending');

        $this->orderCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getOrderStatus')
            ->willReturn('pending');

        $this->orderCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        $this->orderCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerInterfaceMock
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->scopeConfigInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn('pending');

        $this->orderCollectionMock
            ->expects($this->atLeastOnce())
            ->method('getOrderStatus')
            ->willReturn('pending');

        $this->orderCollection->expects($this->once())
            ->method('getColumnValues')
            ->willReturn([1]);

        $this->salesOrderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->salesOrderCollectionMock);

        $this->salesOrderCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->willReturn($this->orderCollection);

        $this->orderCollectionMock->expects($this->atLeastOnce())
            ->method('getStore')
            ->willReturn($this->storeInterfaceMock);

        $this->connectorOrderFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->connectorOrderMock);

        $this->connectorOrderMock->expects($this->atLeastOnce())
            ->method('setOrderData')
            ->willReturn($this->connectorOrderMock);

        $result = $this->exporter->exportOrders(['1','2','3']);

        $this->assertEquals(1, count($result));
    }

    public function fetchMockedOrdersFromIds()
    {
        $this->orderCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock
            ->expects($this->once())
            ->method('getOrdersFromIds')
            ->with(['1','2','3'])
            ->willReturn($this->orderCollection);
    }
}
