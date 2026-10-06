<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product\Provider;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Dotdigitalgroup\Email\Model\Product\Provider\LowestPriceProductProvider;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class LowestPriceProductProviderTest extends TestCase
{
    /**
     * @var ProductProviderInterface|MockObject
     */
    private $productProviderMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var LowestPriceProductProvider
     */
    private $lowestPriceProductProvider;

    protected function setUp(): void
    {
        $this->productProviderMock = $this->createMock(ProductProviderInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->storeMock = $this->createMock(StoreInterface::class);

        $this->storeManagerMock->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->method('getId')
            ->willReturn(1);
    }

    public function testFindLowestPricedProductForConfigurableWithRegularPrice(): void
    {
        $this->lowestPriceProductProvider = new LowestPriceProductProvider(
            $this->productProviderMock,
            $this->storeManagerMock,
            false
        );

        $parentProduct = $this->createConfigurableProduct();
        $childProduct1 = $this->createChildProduct(100.00, 80.00, [1]);
        $childProduct2 = $this->createChildProduct(90.00, 70.00, [1]);
        $childProduct3 = $this->createChildProduct(110.00, 85.00, [1]);

        $configurableType = $this->createMock(Configurable::class);
        $configurableType->method('getUsedProducts')
            ->with($parentProduct)
            ->willReturn([$childProduct1, $childProduct2, $childProduct3]);

        $parentProduct->method('getTypeInstance')
            ->willReturn($configurableType);

        $this->productProviderMock->method('getProduct')
            ->willReturn($parentProduct);

        $result = $this->lowestPriceProductProvider->getProduct();

        $this->assertSame($childProduct2, $result);
    }

    public function testFindLowestPricedProductForConfigurableWithSpecialPrice(): void
    {
        $this->lowestPriceProductProvider = new LowestPriceProductProvider(
            $this->productProviderMock,
            $this->storeManagerMock,
            true
        );

        $parentProduct = $this->createConfigurableProduct();
        $childProduct1 = $this->createChildProduct(100.00, 80.00, [1]);
        $childProduct2 = $this->createChildProduct(90.00, 70.00, [1]);
        $childProduct3 = $this->createChildProduct(110.00, 85.00, [1]);

        $configurableType = $this->createMock(Configurable::class);
        $configurableType->method('getUsedProducts')
            ->with($parentProduct)
            ->willReturn([$childProduct1, $childProduct2, $childProduct3]);

        $parentProduct->method('getTypeInstance')
            ->willReturn($configurableType);

        $this->productProviderMock->method('getProduct')
            ->willReturn($parentProduct);

        $result = $this->lowestPriceProductProvider->getProduct();

        $this->assertSame($childProduct2, $result);
    }

    public function testFindLowestPricedProductForGroupedWithRegularPrice(): void
    {
        $this->lowestPriceProductProvider = new LowestPriceProductProvider(
            $this->productProviderMock,
            $this->storeManagerMock,
            false
        );

        $parentProduct = $this->createGroupedProduct();
        $childProduct1 = $this->createChildProduct(50.00, 40.00, [1]);
        $childProduct2 = $this->createChildProduct(45.00, 35.00, [1]);
        $childProduct3 = $this->createChildProduct(55.00, 45.00, [1]);

        $groupedType = $this->createMock(Grouped::class);
        $groupedType->method('getAssociatedProducts')
            ->with($parentProduct)
            ->willReturn([$childProduct1, $childProduct2, $childProduct3]);

        $parentProduct->method('getTypeInstance')
            ->willReturn($groupedType);

        $this->productProviderMock->method('getProduct')
            ->willReturn($parentProduct);

        $result = $this->lowestPriceProductProvider->getProduct();

        $this->assertSame($childProduct2, $result);
    }

    public function testFindLowestPricedProductForGroupedWithSpecialPrice(): void
    {
        $this->lowestPriceProductProvider = new LowestPriceProductProvider(
            $this->productProviderMock,
            $this->storeManagerMock,
            true
        );

        $parentProduct = $this->createGroupedProduct();
        $childProduct1 = $this->createChildProduct(50.00, 40.00, [1]);
        $childProduct2 = $this->createChildProduct(45.00, 35.00, [1]);
        $childProduct3 = $this->createChildProduct(55.00, 45.00, [1]);

        $groupedType = $this->createMock(Grouped::class);
        $groupedType->method('getAssociatedProducts')
            ->with($parentProduct)
            ->willReturn([$childProduct1, $childProduct2, $childProduct3]);

        $parentProduct->method('getTypeInstance')
            ->willReturn($groupedType);

        $this->productProviderMock->method('getProduct')
            ->willReturn($parentProduct);

        $result = $this->lowestPriceProductProvider->getProduct();

        $this->assertSame($childProduct2, $result);
    }

    public function testFindLowestPricedProductReturnsParentForSimpleProduct(): void
    {
        $this->lowestPriceProductProvider = new LowestPriceProductProvider(
            $this->productProviderMock,
            $this->storeManagerMock,
            false
        );

        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');

        $this->productProviderMock->method('getProduct')
            ->willReturn($product);

        $result = $this->lowestPriceProductProvider->getProduct();

        $this->assertSame($product, $result);
    }

    public function testFindLowestPricedProductFallsBackToRegularPriceWhenNoSpecialPrice(): void
    {
        $this->lowestPriceProductProvider = new LowestPriceProductProvider(
            $this->productProviderMock,
            $this->storeManagerMock,
            true
        );

        $parentProduct = $this->createConfigurableProduct();
        $childProduct1 = $this->createChildProduct(100.00, null, [1]);
        $childProduct2 = $this->createChildProduct(90.00, null, [1]);

        $configurableType = $this->createMock(Configurable::class);
        $configurableType->method('getUsedProducts')
            ->with($parentProduct)
            ->willReturn([$childProduct1, $childProduct2]);

        $parentProduct->method('getTypeInstance')
            ->willReturn($configurableType);

        $this->productProviderMock->method('getProduct')
            ->willReturn($parentProduct);

        $result = $this->lowestPriceProductProvider->getProduct();

        $this->assertSame($childProduct2, $result);
    }

    private function createConfigurableProduct(): MockObject
    {
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('configurable');
        return $product;
    }

    private function createGroupedProduct(): MockObject
    {
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('grouped');
        return $product;
    }

    private function createChildProduct(float $price, ?float $specialPrice, array $storeIds): MockObject
    {
        $product = $this->createMock(Product::class);
        $product->method('getPrice')->willReturn($price);
        $product->method('getSpecialPrice')->willReturn($specialPrice);
        $product->method('getStoreIds')->willReturn($storeIds);
        return $product;
    }
}
