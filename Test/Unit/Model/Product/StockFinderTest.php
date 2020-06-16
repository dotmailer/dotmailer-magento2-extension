<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\StockFinder;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemCollectionInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
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

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stockItemCollectionInterfaceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stockItemCriteriaFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stockItemCriteriaMock;

    protected function setUp() :void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->stockItemRepositoryMock = $this->getMockBuilder(StockItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->createMock(Product::class);
        $this->stockItemInterfaceMock = $this->getMockBuilder(StockItemInterface::class);
        $this->typeInstanceMock = $this->createMock(Configurable::class);

        $this->stockItemCriteriaFactoryMock = $this->getMockBuilder(StockItemCriteriaInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','setProductsFilter'])
            ->getMock();

        $this->stockItemCriteriaMock = $this->createMock(StockItemCriteriaInterface::class);

        $this->stockItemCollectionInterfaceMock = $this->getMockBuilder(StockItemCollectionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(array_merge(get_class_methods(StockItemCollectionInterface::class), ['getSize']))
            ->getMock();

        $this->stockFinder = new StockFinder(
            $this->stockItemRepositoryMock,
            $this->loggerMock,
            $this->stockItemCriteriaFactoryMock
        );
    }

    public function testSimpleProductStockQty()
    {
        $qty = 10;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->stockItemCriteriaFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->stockItemCriteriaMock);

        $this->stockItemRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->stockItemCriteriaMock)
            ->willReturn($this->stockItemCollectionInterfaceMock);

        $this->stockItemCollectionInterfaceMock->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->stockItemCollectionInterfaceMock->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([$this->productMock]);

        $this->productMock->expects($this->once())
            ->method('getQty')
            ->willReturn($qty);

        $stock = $this->stockFinder->getStockQty($this->productMock);

        $this->assertEquals($stock, $qty);
    }

    public function testConfigurableProductStockQty()
    {
        $numberOfChildren = 15;
        $qty = 10;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->productMock->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($this->typeInstanceMock);

        $this->typeInstanceMock->expects($this->once())
            ->method('getUsedProducts')
            ->with($this->productMock)
            ->willReturn($this->getSimpleProducts($numberOfChildren));

        $this->stockItemCriteriaFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->stockItemCriteriaMock);

        $this->stockItemRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->stockItemCriteriaMock)
            ->willReturn($this->stockItemCollectionInterfaceMock);

        $this->stockItemCollectionInterfaceMock->expects($this->once())
            ->method('getSize')
            ->willReturn($numberOfChildren);

        $this->stockItemCollectionInterfaceMock->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn($this->getSimpleProducts($numberOfChildren));

        $this->productMock->expects($this->atLeastOnce())
            ->method('getQty')
            ->willReturn($qty);

        $stock = $this->stockFinder->getStockQty($this->productMock);

        $this->assertEquals($stock, $numberOfChildren * $qty);
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
