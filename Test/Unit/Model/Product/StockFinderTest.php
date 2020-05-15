<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\StockFinder;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use PHPUnit\Framework\TestCase;

class StockFinderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var StockFinder
     */
    private $stockFinder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stockItemRepositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stockRegistryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $productMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stockItemInterfaceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $typeInstanceMock;

    protected function setUp() :void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->stockItemRepositoryMock = $this->createMock(StockItemRepositoryInterface::class);
        $this->stockRegistryMock = $this->createMock(StockRegistryInterface::class);
        $this->productMock = $this->createMock(Product::class);
        $this->stockItemInterfaceMock = $this->createMock(StockItemInterface::class);
        $this->typeInstanceMock = $this->createMock(Configurable::class);

        $this->stockFinder = new StockFinder(
            $this->stockItemRepositoryMock,
            $this->stockRegistryMock,
            $this->loggerMock
        );
    }

    public function testSimpleProductStockQty()
    {
        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->stockRegistryMock->expects($this->once())
            ->method('getStockItem')
            ->willReturn($this->stockItemInterfaceMock);

        $this->stockItemInterfaceMock->expects($this->once())
            ->method('getItemId');

        $this->stockItemRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($this->stockItemInterfaceMock);

        $this->stockItemInterfaceMock->expects($this->once())
            ->method('getQty');

        $this->stockFinder->getStockQty($this->productMock);
    }

    public function testConfigurableProductStockQty()
    {
        $numberOfChildren = 15;

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->productMock->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($this->typeInstanceMock);

        $this->typeInstanceMock->expects($this->once())
            ->method('getUsedProducts')
            ->with($this->productMock)
            ->willReturn($this->getSimpleProducts($numberOfChildren));

        $this->stockRegistryMock->expects($this->exactly($numberOfChildren))
            ->method('getStockItem')
            ->willReturn($this->stockItemInterfaceMock);

        $this->stockItemRepositoryMock->expects($this->exactly($numberOfChildren))
            ->method('get')
            ->willReturn($this->stockItemInterfaceMock);

        $this->stockItemInterfaceMock->expects($this->exactly($numberOfChildren))
            ->method('getQty');

        $this->stockFinder->getStockQty($this->productMock);
    }

    /**
     * @param $numberOfChildren
     * @return array
     */
    private function getSimpleProducts($numberOfChildren)
    {
        $simpleProducts = [];

        for ($i=0; $i < $numberOfChildren; $i++) {
            $simpleProducts[] = $this->productMock;
        }

        return $simpleProducts;
    }
}
