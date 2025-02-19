<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Model\Product\PriceFinder;
use Magento\Bundle\Pricing\Price\BundleRegularPrice;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Pricing\Amount\Base as AmountBase;
use Magento\Framework\Pricing\PriceInfo\Base;
use Magento\Tax\Api\TaxCalculationInterface;
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
     * @var TaxCalculationInterface|MockObject
     */
    private $taxCalculationMock;

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
        $this->taxCalculationMock = $this->createMock(TaxCalculationInterface::class);
        $this->taxHelperMock = $this->createMock(TaxHelper::class);

        $this->priceFinder = new PriceFinder(
            $this->taxCalculationMock,
            $this->taxHelperMock
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

        $this->baseMock->expects($this->atLeastonce())
            ->method('getPrice')
            ->withConsecutive(['regular_price'], ['final_price'])
            ->willReturnOnConsecutiveCalls($this->bundleRegularPriceMock, $this->bundleRegularPriceMock);

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

    public function testSetPricesIncTaxIfPricesShouldIncludeTax()
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

        $this->taxHelperMock->expects($this->once())
            ->method('priceIncludesTax')
            ->willReturn(true);

        $this->taxCalculationMock->expects($this->never())
            ->method('getCalculatedRate');

        $priceInclTax = $this->priceFinder->getPriceInclTax($this->productMock, 1);
        $specialPriceInclTax = $this->priceFinder->getSpecialPriceInclTax($this->productMock, 1);

        $this->assertEquals($price_incl_tax, $priceInclTax);
        $this->assertEquals($specialPrice_incl_tax, $specialPriceInclTax);
    }

    public function testSetPricesIncTaxIfPricesShouldNotIncludeTax()
    {
        $price = '20.00';
        $price_incl_tax = '24.00';
        $specialPrice = '15.00';
        $specialPrice_incl_tax = '18.00';
        $taxableGoodsClassId = 2;
        $taxRate = 20.0;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->productMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        $this->productMock->expects($this->atLeastOnce())
            ->method('getSpecialPrice')
            ->willReturn($specialPrice);

        $this->taxHelperMock->expects($this->once())
            ->method('priceIncludesTax')
            ->willReturn(false);

        $this->productMock->expects($this->once())
            ->method('getTaxClassId')
            ->willReturn($taxableGoodsClassId);

        $this->taxCalculationMock->expects($this->once())
            ->method('getCalculatedRate')
            ->with($taxableGoodsClassId, null, 1)
            ->willReturn($taxRate);

        $priceInclTax = $this->priceFinder->getPriceInclTax($this->productMock, 1);
        $specialPriceInclTax = $this->priceFinder->getSpecialPriceInclTax($this->productMock, 1);

        $this->assertEquals($price_incl_tax, $priceInclTax);
        $this->assertEquals($specialPrice_incl_tax, $specialPriceInclTax);
    }

    public function testSetPricesIncTaxIfPricesShouldNotIncludeTaxAndCustomerIdSet()
    {
        $price = '20.00';
        $price_incl_tax = '24.00';
        $specialPrice = '15.00';
        $specialPrice_incl_tax = '18.00';
        $taxableGoodsClassId = 2;
        $taxRate = 20.0;
        $storeId = 1;
        $customerId = 1;

        $this->productMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->productMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        $this->productMock->expects($this->atLeastOnce())
            ->method('getSpecialPrice')
            ->willReturn($specialPrice);

        $this->taxHelperMock->expects($this->once())
            ->method('priceIncludesTax')
            ->willReturn(false);

        $this->productMock->expects($this->once())
            ->method('getTaxClassId')
            ->willReturn($taxableGoodsClassId);

        $this->taxCalculationMock->expects($this->once())
            ->method('getCalculatedRate')
            ->with($taxableGoodsClassId, $customerId, 1)
            ->willReturn($taxRate);

        $priceInclTax = $this->priceFinder->getPriceInclTax($this->productMock, $storeId, $customerId);
        $specialPriceInclTax = $this->priceFinder->getSpecialPriceInclTax($this->productMock, $storeId, $customerId);

        $this->assertEquals($price_incl_tax, $priceInclTax);
        $this->assertEquals($specialPrice_incl_tax, $specialPriceInclTax);
    }

    private function getConfigurableChildProducts()
    {
        $firstElement = $this->createMock(Product::class);
        $firstElement->expects($this->once())->method('getStoreIds')->willReturn([1]);
        $firstElement->expects($this->once())->method('getPrice')->willReturn('20.00');
        $firstElement->expects($this->exactly(2))
            ->method('getSpecialPrice')
            ->willReturn('15.00');

        $secondElement = $this->createMock(Product::class);
        $secondElement->expects($this->once())->method('getStoreIds')->willReturn([1]);
        $secondElement->expects($this->once())->method('getPrice')->willReturn('15.00');
        $secondElement->expects($this->exactly(2))
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
        $firstElement->expects($this->exactly(2))
            ->method('getSpecialPrice')
            ->willReturn('15.00');

        $secondElement = $this->createMock(Product::class);
        $secondElement->expects($this->once())->method('getPrice')->willReturn('15.00');
        $secondElement->expects($this->exactly(2))
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
