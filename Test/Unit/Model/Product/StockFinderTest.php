<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\Stock\SalableQuantity;
use Dotdigitalgroup\Email\Model\Product\StockFinder;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use PHPUnit\Framework\TestCase;

class StockFinderTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var StockFinder
     */
    private $stockFinder;

    /**
     * @var SalableQuantity|\PHPUnit\Framework\MockObject\MockObject
     */
    private $salableQuantityMock;

    /**
     * @var SourceItemRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sourceItemRepositoryMock;

    /**
     * @var Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $typeInstanceMock;

    /**
     * @var SearchCriteriaInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaMock;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var SourceItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sourceItemInterfaceMock;

    /**
     * @var SourceItemSearchResultsInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sourceItemSearchResultsInterfaceMock;

    protected function setUp() :void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->salableQuantityMock = $this->createMock(SalableQuantity::class);
        $this->sourceItemRepositoryMock = $this->createMock(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->productMock = $this->createMock(Product::class);
        $this->sourceItemInterfaceMock = $this->createMock(SourceItemInterface::class);
        $this->typeInstanceMock = $this->createMock(Configurable::class);
        $this->sourceItemSearchResultsInterfaceMock = $this->createMock(SourceItemSearchResultsInterface::class);

        $this->stockFinder = new StockFinder(
            $this->loggerMock,
            $this->salableQuantityMock,
            $this->searchCriteriaBuilderMock,
            $this->scopeConfigInterfaceMock,
            $this->sourceItemRepositoryMock
        );
    }

    public function testSimpleProductStockQty()
    {
        $qty = 10;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        // manage stock = 1
        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(1);

        $this->salableQuantityMock->expects($this->once())
            ->method('getSalableQuantity')
            ->willReturn($qty);

        $stock = $this->stockFinder->getStockQty($this->productMock, 1);

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

        // manage stock (global) = 1
        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(1);

        $this->salableQuantityMock->expects($this->atLeast($numberOfChildren))
            ->method('getSalableQuantity')
            ->willReturn($qty);

        $stock = $this->stockFinder->getStockQty($this->productMock, 1);

        $this->assertEquals($stock, $numberOfChildren * $qty);
    }

    public function testSimpleProductWithGlobalNotManageStock()
    {
        $qty = 10.0;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        // manage stock = 0
        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(0);

        $this->productMock->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn('CHAZ-SKU-001');

        $this->salableQuantityMock->expects($this->never())
            ->method('getSalableQuantity');

        $this->loadInventorySourceItems();

        // single source item returned
        $this->sourceItemSearchResultsInterfaceMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->sourceItemInterfaceMock]);

        $this->sourceItemInterfaceMock->expects($this->once())
            ->method('getQuantity')
            ->willReturn($qty);

        $stock = $this->stockFinder->getStockQty($this->productMock, 1);

        $this->assertEquals($stock, $qty);
    }

    public function testGetStockQtyForDefaultLevelCatalogSync()
    {
        $websiteId = 0;
        $qty = 10.0;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(1);

        $this->productMock->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn('CHAZ-SKU-001');

        $this->salableQuantityMock->expects($this->never())
            ->method('getSalableQuantity');

        $this->loadInventorySourceItems();

        // single source item returned
        $this->sourceItemSearchResultsInterfaceMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->sourceItemInterfaceMock]);

        $this->sourceItemInterfaceMock->expects($this->once())
            ->method('getQuantity')
            ->willReturn($qty);

        $stock = $this->stockFinder->getStockQty($this->productMock, $websiteId);

        $this->assertEquals($stock, $qty);
    }

    public function testSimpleProductWithMultipleFallbackSources()
    {
        $qty = 25.2;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        // manage stock = 1
        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->willReturn(1);

        $this->productMock->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn('CHAZ-SKU-001');

        $this->salableQuantityMock->expects($this->once())
            ->method('getSalableQuantity')
            ->willThrowException(new \Exception());

        $this->loadInventorySourceItems();

        // multiple source items returned
        $this->sourceItemSearchResultsInterfaceMock->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $this->sourceItemInterfaceMock,
                $this->sourceItemInterfaceMock
            ]);

        $this->sourceItemInterfaceMock->expects($this->atLeastOnce())
            ->method('getQuantity')
            ->willReturnOnConsecutiveCalls(10.0, 15.2);

        $stock = $this->stockFinder->getStockQty($this->productMock, 1);

        $this->assertEquals($stock, $qty);
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

    private function loadInventorySourceItems()
    {
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->willReturn($this->searchCriteriaBuilderMock);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->sourceItemRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->sourceItemSearchResultsInterfaceMock);
    }
}
