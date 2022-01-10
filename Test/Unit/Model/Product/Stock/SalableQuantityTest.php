<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Model\Product\Stock\SalableQuantity;
use Magento\Catalog\Model\Product;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\TestCase;

class SalableQuantityTest extends TestCase
{
    /**
     * @var SalableQuantity
     */
    private $salableQuantity;

    /**
     * @var Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productMock;

    /**
     * @var GetProductSalableQtyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $getProductSalableQtyMock;

    /**
     * @var GetAssignedStockIdsBySku|\PHPUnit\Framework\MockObject\MockObject
     */
    private $getAssignedStockIdsBySkuMock;

    /**
     * @var GetStockItemConfigurationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $getStockItemConfigurationMock;

    /**
     * @var GetAssignedSalesChannelsForStockInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $getAssignedSalesChannelsForStockMock;

    /**
     * @var WebsiteRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteRepositoryMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteMock;

    /**
     * @var StockItemConfigurationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $stockItemConfigurationInterfaceMock;

    /**
     * @var SalesChannelInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $salesChannelInterfaceMock;

    protected function setUp() :void
    {
        $this->productMock = $this->createMock(Product::class);
        $this->getProductSalableQtyMock = $this->createMock(GetProductSalableQtyInterface::class);
        $this->getAssignedStockIdsBySkuMock = $this->createMock(GetAssignedStockIdsBySku::class);
        $this->getStockItemConfigurationMock = $this->createMock(GetStockItemConfigurationInterface::class);
        $this->getAssignedSalesChannelsForStockMock = $this->createMock(
            GetAssignedSalesChannelsForStockInterface::class
        );
        $this->websiteRepositoryMock = $this->createMock(WebsiteRepositoryInterface::class);
        $this->websiteMock = $this->createMock(WebsiteInterface::class);
        $this->stockItemConfigurationInterfaceMock = $this->createMock(StockItemConfigurationInterface::class);
        $this->salesChannelInterfaceMock = $this->createMock(SalesChannelInterface::class);

        $this->salableQuantity = new SalableQuantity(
            $this->getProductSalableQtyMock,
            $this->getAssignedStockIdsBySkuMock,
            $this->getStockItemConfigurationMock,
            $this->getAssignedSalesChannelsForStockMock,
            $this->websiteRepositoryMock
        );
    }

    public function testGetSalableQuantityFromAvailableStock()
    {
        $qtyFromStock1 = 50.0;
        $qtyFromStock2 = 100.0;
        $stockIds = [1, 2];
        $websiteCode = 'base';

        $this->productMock->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn('CHAZ-SKU-001');

        $this->getAssignedStockIdsBySkuMock->expects($this->once())
            ->method('execute')
            ->willReturn($stockIds);

        $this->getStockItemConfigurationMock->expects($this->atLeast(count($stockIds)))
            ->method('execute')
            ->willReturn($this->stockItemConfigurationInterfaceMock);

        $this->stockItemConfigurationInterfaceMock->expects($this->atLeast(count($stockIds)))
            ->method('isManageStock')
            ->willReturn(true);

        $this->getAssignedSalesChannelsForStockMock->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn([$this->salesChannelInterfaceMock]);

        $this->salesChannelInterfaceMock->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn(SalesChannelInterface::TYPE_WEBSITE);

        $this->salesChannelInterfaceMock->expects($this->atLeast(count($stockIds)))
            ->method('getCode')
            ->willReturnOnConsecutiveCalls($websiteCode, 'second_website');

        $this->websiteRepositoryMock->expects($this->atLeastOnce())
            ->method('getById')
            ->willReturn($this->websiteMock);

        $this->websiteMock->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn($websiteCode);

        $this->getProductSalableQtyMock->expects($this->once())
            ->method('execute')
            ->willReturnOnConsecutiveCalls($qtyFromStock1, $qtyFromStock2);

        $stock = $this->salableQuantity->getSalableQuantity($this->productMock, 1);

        $this->assertEquals($stock, $qtyFromStock1);
    }

    public function testExceptionThrownIfNoManageStockForSku()
    {
        $stockIds = [1, 2];

        $this->productMock->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn('CHAZ-SKU-001');

        $this->getAssignedStockIdsBySkuMock->expects($this->once())
            ->method('execute')
            ->willReturn($stockIds);

        $this->getStockItemConfigurationMock->expects($this->atLeast(count($stockIds)))
            ->method('execute')
            ->willReturn($this->stockItemConfigurationInterfaceMock);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);

        $this->stockItemConfigurationInterfaceMock->expects($this->atLeast(count($stockIds)))
            ->method('isManageStock')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->salableQuantity->getSalableQuantity($this->productMock, 1);
    }
}
