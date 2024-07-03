<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation;

use Dotdigital\Resources\AbstractResource;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\OrderManager;
use Dotdigitalgroup\Email\Model\Sync\Order\Exporter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderManagerTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var AutomationTypeHandler|MockObject
     */
    private $automationTypeHandlerMock;

    /**
     * @var Exporter|MockObject
     */
    private $exporterMock;

    /**
     * @var SalesOrderCollectionFactory|MockObject
     */
    private $salesOrderCollectionFactoryMock;

    /**
     * @var OrderManager
     */
    private $orderManager;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientFactoryMock = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->automationTypeHandlerMock = $this->getMockBuilder(AutomationTypeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->exporterMock = $this->getMockBuilder(Exporter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->salesOrderCollectionFactoryMock = $this->getMockBuilder(SalesOrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderManager = new OrderManager(
            $this->loggerMock,
            $this->clientFactoryMock,
            $this->automationTypeHandlerMock,
            $this->exporterMock,
            $this->salesOrderCollectionFactoryMock
        );
    }

    public function testMaybeDoSendOrderInsightData(): void
    {
        $automationType = 'order_automation';

        $automation = $this->getMockBuilder(Automation::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAutomationType', 'getTypeId', 'getEmail', 'getWebsiteId'])
            ->getMock();

        $automation->expects($this->once())
            ->method('getAutomationType')
            ->willReturn($automationType);

        $this->automationTypeHandlerMock->expects($this->once())
            ->method('isOrderTypeAutomation')
            ->with($automationType)
            ->willReturn(true);

        $automation->expects($this->once())
            ->method('getTypeId')
            ->willReturn('10000001');

        $automation->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $automation->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->loggerMock->expects($this->never())
            ->method('debug');

        $salesOrderCollectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $this->salesOrderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($salesOrderCollectionMock);

        $this->exporterMock->expects($this->once())
            ->method('mapOrderData')
            ->willReturn(['1' => ['order_data']]);

        $clientMock = $this->createMock(Client::class);
        $abstractResourceMock = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->addMethods(['createOrUpdateContactCollectionRecord'])
            ->getMock();
        $clientMock->insightData = $abstractResourceMock;

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($clientMock);

        $abstractResourceMock->expects($this->once())
            ->method('createOrUpdateContactCollectionRecord');

        $this->orderManager->maybeSendOrderInsightData($automation);
    }

    public function testMaybeDontSendOrderInsightData(): void
    {
        $automationType = 'customer_automation';

        $automation = $this->getMockBuilder(Automation::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAutomationType', 'getTypeId', 'getEmail', 'getWebsiteId'])
            ->getMock();

        $automation->expects($this->once())
            ->method('getAutomationType')
            ->willReturn($automationType);

        $this->automationTypeHandlerMock->expects($this->once())
            ->method('isOrderTypeAutomation')
            ->with($automationType)
            ->willReturn(false);

        $automation->expects($this->never())
            ->method('getTypeId');

        $automation->expects($this->never())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->orderManager->maybeSendOrderInsightData($automation);
    }

    public function testMaybeSendOrderInsightDataWithException(): void
    {
        $automationType = 'order_automation';

        $automation = $this->getMockBuilder(Automation::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAutomationType', 'getTypeId', 'getEmail', 'getWebsiteId'])
            ->getMock();

        $automation->expects($this->once())
            ->method('getAutomationType')
            ->willReturn($automationType);

        $this->automationTypeHandlerMock->expects($this->once())
            ->method('isOrderTypeAutomation')
            ->with($automationType)
            ->willReturn(true);

        $automation->expects($this->once())
            ->method('getTypeId')
            ->willReturn('10000001');

        $automation->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $automation->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn('1');

        $salesOrderCollectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $this->salesOrderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($salesOrderCollectionMock);

        $this->exporterMock->expects($this->once())
            ->method('mapOrderData')
            ->willReturn([]);

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('No order data prepared for order increment id 10000001');

        $this->orderManager->maybeSendOrderInsightData($automation);
    }
}
