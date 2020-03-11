<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Magento\CatalogInventory\Model\Stock\StockItemRepository;
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
    private $productMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stockItemInterfaceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $typeInstanceMock;

    protected function setUp()
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->stockItemRepositoryMock = $this->createMock(StockItemRepository::class);
        $this->productMock = $this->createMock(Product::class);
        $this->stockItemInterfaceMock = $this->createMock(StockItemInterface::class);
        $this->typeInstanceMock = $this->createMock(Configurable::class);

        $this->stockFinder = new StockFinder(
            $this->stockItemRepositoryMock,
            $this->loggerMock
        );
    }

    public function testSimpleProductStockQty()
    {
        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->stockItemRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($this->stockItemInterfaceMock);

        $this->stockItemInterfaceMock->expects($this->once())
            ->method('getQty');

        $this->stockFinder->getStockQty($this->productMock);
    }

    public function testConfigurableProdictStockQty()
    {
        $numberOfChilds = 15;

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->productMock->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($this->typeInstanceMock);

        $this->typeInstanceMock->expects($this->once())
            ->method('getUsedProducts')
            ->with($this->productMock)
            ->willReturn($this->getSimpleProducts($numberOfChilds));

        $this->stockItemRepositoryMock->expects($this->exactly($numberOfChilds))
            ->method('get')
            ->willReturn($this->stockItemInterfaceMock);

        $this->stockItemInterfaceMock->expects($this->exactly($numberOfChilds))
            ->method('getQty');

        $this->stockFinder->getStockQty($this->productMock);
    }

    /**
     * @param $numberOfChilds
     * @return array
     */
    private function getSimpleProducts($numberOfChilds)
    {
        $simpleProducts = [];

        for ($i=0; $i<$numberOfChilds; $i++) {
            $simpleProducts[] = $this->productMock;
        }

        return $simpleProducts;
    }
}
