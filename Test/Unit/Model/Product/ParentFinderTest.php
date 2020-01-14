<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\ResourceModel\Selection;
use PHPUnit\Framework\TestCase;

class ParentFinderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $productRepositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $configurableTypeMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $groupedTypeMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $bundleSelectionMock;

    /**
     * @var ParentFinder
     */
    private $parentFinder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $productMock;

    protected function setUp()
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->configurableTypeMock = $this->createMock(Configurable::class);
        $this->groupedTypeMock = $this->createMock(Grouped::class);
        $this->bundleSelectionMock = $this->createMock(Selection::class);
        $this->productMock = $this->createMock(Product::class);

        $this->parentFinder = new ParentFinder(
            $this->productRepositoryMock,
            $this->helperMock,
            $this->configurableTypeMock,
            $this->groupedTypeMock,
            $this->bundleSelectionMock
        );
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

    public function testFetchForSimpleNotVisibleProductWithGroupedTypeParent()
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

        $this->parentFinder->getParentProduct($this->productMock);
    }

    /**
     * Builds all the mutual assertions for all cases
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildAssertions()
    {

        // New Product mock for parent
        $parentProduct = $this->createMock(Product::class);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with(10)
            ->willReturn($parentProduct);

        $this->parentFinder->getParentProduct($this->productMock);
    }
}
