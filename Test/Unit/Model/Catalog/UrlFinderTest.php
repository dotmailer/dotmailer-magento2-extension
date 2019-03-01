<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Catalog\UrlFinder;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Model\Product;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder as UrlFinder;
use Magento\Bundle\Model\ResourceModel\Selection;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use PHPUnit\Framework\TestCase;

class UrlFinderTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepositoryMock;

    /**
     * @var Configurable|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configurableTypeMock;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productMock;

    /**
     * @var Selection
     */
    private $bundleSelectionMock;

    /**
     * @var Grouped
     */
    private $groupedTypeMock;

    /**
     * @var UrlFinder
     */
    private $urlFinder;

    protected function setUp()
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->configurableTypeMock = $this->createMock(Configurable::class);
        $this->productMock = $this->createMock(Product::class);
        $this->bundleSelectionMock = $this->createMock(Selection::class);
        $this->groupedTypeMock = $this->createMock(Grouped::class);

        $this->urlFinder = new UrlFinder(
            $this->configurableTypeMock,
            $this->productRepositoryMock,
            $this->bundleSelectionMock,
            $this->groupedTypeMock
        );
    }

    public function testFetchForSimpleVisibleProduct()
    {
        // corresponds to Magento's constant values for visibility levels
        $visibleInCatalogAndSearchInt = 4;

        $this->productMock->expects($this->once())
            ->method('getVisibility')
            ->willReturn($visibleInCatalogAndSearchInt);

        $this->productMock->expects($this->once())
            ->method('getProductUrl');

        $this->productRepositoryMock->expects($this->never())
            ->method('getById');

        $this->urlFinder->fetchFor($this->productMock);
    }

    public function testFetchForSimpleNotVisibleProductWithConfigurableTypeParent()
    {

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn([10]);

        $this->groupedTypeMock->expects($this->never())
            ->method('getParentIdsByChild');

        $this->bundleSelectionMock->expects($this->never())
            ->method('getParentIdsByChild');

        $this->buildAssertions();

    }

    public function testFetchForSimpleNotVisibleProductsWithGroupedTypeParent()
    {
        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->groupedTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn([10]);

        $this->bundleSelectionMock->expects($this->never())
            ->method('getParentIdsByChild');

        $this->buildAssertions();
    }

    public function testFetchForSimpleNotVisibleProductWithBundleTypeParent()
    {
        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->groupedTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->bundleSelectionMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn([10]);

        $this->buildAssertions();
    }

    public function testFetchForSimpleNotVisibleProductWithNoParent()
    {
        $notVisibleInt =1;

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->groupedTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->bundleSelectionMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->productMock->expects($this->once())
            ->method('getVisibility')
            ->willReturn($notVisibleInt);

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->productRepositoryMock->expects($this->never())
            ->method('getById');

        $this->productMock->expects($this->once())
            ->method('getProductUrl');

        $this->urlFinder->fetchFor($this->productMock);
    }

    /**
     * Builds all the mutual assertions for all cases
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildAssertions()
    {
        // corresponds to Magento's constant values for visibility levels
        $notVisibleInt = 1;

        $this->productMock->expects($this->once())
            ->method('getVisibility')
            ->willReturn($notVisibleInt);

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        // New Product mock for parent
        $parentProduct = $this->createMock(Product::class);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with(10)
            ->willReturn($parentProduct);

        $parentProduct->expects($this->once())
            ->method('getProductUrl');

        $this->urlFinder->fetchFor($this->productMock);
    }
}
