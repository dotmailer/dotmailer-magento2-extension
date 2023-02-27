<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Model\Sync\Subscriber\OrderHistoryChecker;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory;
use PHPUnit\Framework\TestCase;

class OrderHistoryCheckerTest extends TestCase
{
    /**
     * @var OrderSearchResultInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderSearchResultInterfaceFactoryMock;

    /**
     * @var OrderHistoryChecker
     */
    private $orderHistoryChecker;

    protected function setUp(): void
    {
        $this->orderSearchResultInterfaceFactoryMock = $this->createMock(OrderSearchResultInterfaceFactory::class);

        $this->orderHistoryChecker = new OrderHistoryChecker(
            $this->orderSearchResultInterfaceFactoryMock
        );
    }

    public function testCheckInSales()
    {
        $orderSearchResultsInterfaceMock = $this->createMock(AbstractDb::class);
        $this->orderSearchResultInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderSearchResultsInterfaceMock);

        $orderSearchResultsInterfaceMock->expects($this->once())
            ->method('addFieldToFilter')
            ->willReturn($orderSearchResultsInterfaceMock);

        $orderSearchResultsInterfaceMock->expects($this->once())
            ->method('getColumnValues')
            ->willReturn(['purchaser@emailsim.io']);

        $inSales = $this->orderHistoryChecker->checkInSales([
            'chaz@emailsim.io',
            'guest@emailsim.io',
            'purchaser@emailsim.io'
        ]);

        $this->assertCount(1, $inSales);
    }
}
