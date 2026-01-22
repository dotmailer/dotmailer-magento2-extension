<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Model\Product\PriceFinder;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Bundle\Pricing\Price\BundleRegularPrice;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Pricing\Amount\Base as AmountBase;
use Magento\Framework\Pricing\PriceInfo\Base;
use Magento\Tax\Helper\Data as TaxHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PriceFinderTest extends TestCase
{
    /**
     * @var Product|MockObject
     */
    private $productMock;

    /**
     * @var Configurable|MockObject
     */
    private $configurableMock;

    /**
     * @var Base|MockObject
     */
    private $baseMock;

    /**
     * @var BundleRegularPrice|MockObject
     */
    private $bundleRegularPriceMock;

    /**
     * @var AmountBase|MockObject
     */
    private $amountBaseMock;

    /**
     * @var TaxCalculator|MockObject
     */
    private $taxCalculatorMock;

    /**
     * @var TaxHelper|MockObject
     */
    private $taxHelperMock;

    /**
     * @var PriceFinder
     */
    private $priceFinder;

    protected function setup(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTypeInstance',
                    'getTypeId',
                    'getPrice',
                    'getSpecialPrice',
                    'getPriceInfo'
                ]
            )
            ->addMethods(
                [
                    'getTaxClassId'
                ]
            )
            ->getMock();
        $this->baseMock = $this->createMock(Base::class);
        $this->bundleRegularPriceMock = $this->createMock(BundleRegularPrice::class);
        $this->amountBaseMock = $this->createMock(AmountBase::class);
        $this->configurableMock = $this->createMock(Configurable::class);
        $this->taxCalculatorMock = $this->createMock(TaxCalculator::class);

        $this->priceFinder = new PriceFinder(
            $this->taxCalculatorMock
        );
    }

    public function testConfigurableMinPrice()
    {
        $minPrice = '15.00';
        $minSpecialPrice = '8.00';

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $childProducts = $this->getConfigurableChildProducts();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getUsedProducts')
            ->with($this->productMock)
            ->willReturn($childProducts);

        $price = $this->priceFinder->getPrice($this->productMock, 1);
        $specialPrice = $this->priceFinder->getSpecialPrice($this->productMock, 1);

        $this->assertEquals($minPrice, $price);
        $this->assertEquals($minSpecialPrice, $specialPrice);
    }

    public function testConfigurableMinPriceIfProductNotInStore()
    {
        $minPrice = '0';
        $minSpecialPrice = '0';

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $childProducts = $this->getConfigurableChildProductsNotInStore();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getUsedProducts')
            ->with($this->productMock)
            ->willReturn($childProducts);

        $price = $this->priceFinder->getPrice($this->productMock, 1);
        $specialPrice = $this->priceFinder->getSpecialPrice($this->productMock, 1);

        $this->assertEquals($minPrice, $price);
        $this->assertEquals($minSpecialPrice, $specialPrice);
    }

    public function testConfigurableMinSpecialPriceIsZeroIfSpecialPriceIsNull()
    {
        $minPrice = '15.00';
        $minSpecialPrice = '0.0';

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $childProducts = $this->getConfigurableNullSpecialPrices();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getUsedProducts')
            ->with($this->productMock)
            ->willReturn($childProducts);

        $price = $this->priceFinder->getPrice($this->productMock, 1);
        $specialPrice = $this->priceFinder->getSpecialPrice($this->productMock, 1);

        $this->assertEquals($minPrice, $price);
        $this->assertEquals($minSpecialPrice, $specialPrice);
    }

    public function testBundleMinPrice()
    {
        $minPrice = '10.00';
        $minSpecialPrice = '8.00';

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('bundle');

        $this->productMock->expects($this->atLeastOnce())
            ->method('getPriceInfo')
            ->willReturn($this->baseMock);

        $matcher = $this->exactly(2);
        $this->baseMock->expects($this->atLeastonce())
            ->method('getPrice')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->numberOfInvocations()) {
                    1 => ['regular_price'],
                    2 => ['final_price']
                };
            })
            ->willReturnOnConsecutiveCalls(
                $this->bundleRegularPriceMock,
                $this->bundleRegularPriceMock
            );

        $this->bundleRegularPriceMock->expects($this->atLeastOnce())
            ->method('getMinimalPrice')
            ->willReturnOnConsecutiveCalls($this->amountBaseMock, $this->amountBaseMock);

        $this->amountBaseMock->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('10.00', '8.00');

        $price = $this->priceFinder->getPrice($this->productMock, 1);
        $specialPrice = $this->priceFinder->getSpecialPrice($this->productMock, 1);

        $this->assertEquals($minPrice, $price);
        $this->assertEquals($minSpecialPrice, $specialPrice);
    }

    public function testGroupedProductsMinPrice()
    {
        $minPrice = '15.00';
        $minSpecialPrice = '8.00';

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('grouped');

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $arrayPrices = $this->getGroupedChildProducts();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getAssociatedProducts')
            ->with($this->productMock)
            ->willReturn($arrayPrices);

        $price = $this->priceFinder->getPrice($this->productMock, 1);
        $specialPrice = $this->priceFinder->getSpecialPrice($this->productMock, 1);

        $this->assertEquals($minPrice, $price);
        $this->assertEquals($minSpecialPrice, $specialPrice);
    }

    public function testGroupedProductsMinSpecialPriceIsZeroIfSpecialPriceIsNull()
    {
        $minPrice = '15.00';
        $minSpecialPrice = '0.0';

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('grouped');

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $arrayPrices = $this->getGroupedNullSpecialPrices();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getAssociatedProducts')
            ->with($this->productMock)
            ->willReturn($arrayPrices);

        $price = $this->priceFinder->getPrice($this->productMock, 1);
        $specialPrice = $this->priceFinder->getSpecialPrice($this->productMock, 1);

        $this->assertEquals($minPrice, $price);
        $this->assertEquals($minSpecialPrice, $specialPrice);
    }

    public function testSetPricesIncTax()
    {
        $price = 20.00;
        $price_incl_tax = 20.00;
        $specialPrice = 15.00;
        $specialPrice_incl_tax = 15.00;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->productMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        $this->productMock->expects($this->atLeastOnce())
            ->method('getSpecialPrice')
            ->willReturn($specialPrice);

        $matcher = $this->exactly(2);
        $this->taxCalculatorMock->expects($matcher)
            ->method('calculatePriceInclTax')
            ->willReturnCallback(function () use ($matcher, $price, $specialPrice) {
                return match ($matcher->getInvocationCount()) {
                    1 => [$this->productMock, $price, 1],
                    2 => [$this->productMock, $specialPrice, 1]
                };
            })
            ->willReturnOnConsecutiveCalls(
                $price_incl_tax,
                $specialPrice_incl_tax
            );

        $priceInclTax = $this->priceFinder->getPriceInclTax($this->productMock, 1);
        $specialPriceInclTax = $this->priceFinder->getSpecialPriceInclTax($this->productMock, 1);

        $this->assertEquals($price_incl_tax, $priceInclTax);
        $this->assertEquals($specialPrice_incl_tax, $specialPriceInclTax);
    }

    private function getConfigurableChildProducts()
    {
        $firstElement = $this->createMock(Product::class);
        $firstElement->expects($this->once())->method('getStoreIds')->willReturn([1]);
        $firstElement->expects($this->once())->method('getPrice')->willReturn('20.00');
        $firstElement->expects($this->exactly(1))
            ->method('getSpecialPrice')
            ->willReturn('15.00');

        $secondElement = $this->createMock(Product::class);
        $secondElement->expects($this->once())->method('getStoreIds')->willReturn([1]);
        $secondElement->expects($this->once())->method('getPrice')->willReturn('15.00');
        $secondElement->expects($this->exactly(1))
            ->method('getSpecialPrice')
            ->willReturn('8.00');

        return [$firstElement, $secondElement];
    }

    private function getConfigurableChildProductsNotInStore()
    {
        $firstElement = $this->createMock(Product::class);
        $firstElement->expects($this->once())->method('getStoreIds')->willReturn([2]);
        $firstElement->expects($this->never())->method('getPrice');
        $firstElement->expects($this->never())->method('getSpecialPrice');

        $secondElement = $this->createMock(Product::class);
        $secondElement->expects($this->once())->method('getStoreIds')->willReturn([2]);
        $secondElement->expects($this->never())->method('getPrice');
        $secondElement->expects($this->never())->method('getSpecialPrice');

        return [$firstElement, $secondElement];
    }

    private function getGroupedChildProducts()
    {
        $firstElement = $this->createMock(Product::class);
        $firstElement->expects($this->once())->method('getPrice')->willReturn('20.00');
        $firstElement->expects($this->exactly(1))
            ->method('getSpecialPrice')
            ->willReturn('15.00');

        $secondElement = $this->createMock(Product::class);
        $secondElement->expects($this->once())->method('getPrice')->willReturn('15.00');
        $secondElement->expects($this->exactly(1))
            ->method('getSpecialPrice')
            ->willReturn('8.00');

        return [$firstElement, $secondElement];
    }

    private function getConfigurableNullSpecialPrices()
    {
        $firstElement = $this->createMock(Product::class);
        $firstElement->expects($this->once())->method('getStoreIds')->willReturn([1]);
        $firstElement->expects($this->once())->method('getPrice')->willReturn('20.00');
        $firstElement->expects($this->once())->method('getSpecialPrice')->willReturn(null);

        $secondElement = $this->createMock(Product::class);
        $secondElement->expects($this->once())->method('getStoreIds')->willReturn([1]);
        $secondElement->expects($this->once())->method('getPrice')->willReturn('15.00');
        $secondElement->expects($this->once())->method('getSpecialPrice')->willReturn(null);

        return [$firstElement, $secondElement];
    }

    private function getGroupedNullSpecialPrices()
    {
        $firstElement = $this->createMock(Product::class);
        $firstElement->expects($this->once())->method('getPrice')->willReturn('20.00');
        $firstElement->expects($this->once())->method('getSpecialPrice')->willReturn(null);

        $secondElement = $this->createMock(Product::class);
        $secondElement->expects($this->once())->method('getPrice')->willReturn('15.00');
        $secondElement->expects($this->once())->method('getSpecialPrice')->willReturn(null);

        return [$firstElement, $secondElement];
    }
}
