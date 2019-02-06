<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Catalog\UrlFinder;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Model\Product;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder as UrlFinder;
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

    protected function setUp()
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->configurableTypeMock = $this->createMock(Configurable::class);
        $this->productMock = $this->createMock(Product::class);

        $this->class = new UrlFinder(
            $this->configurableTypeMock,
            $this->productRepositoryMock
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

        $this->class->fetchFor($this->productMock);
    }

    public function testFetchForSimpleNotVisibleProduct()
    {
        // corresponds to Magento's constant values for visibility levels
        $notVisibleInt = 1;

        $this->productMock->expects($this->once())
            ->method('getVisibility')
            ->willReturn($notVisibleInt);

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn([10]);

        // New Product mock for parent
        $parentProduct = $this->createMock(Product::class);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with(10)
            ->willReturn($parentProduct);

        $parentProduct->expects($this->once())
            ->method('getProductUrl');

        $this->class->fetchFor($this->productMock);
    }
}
