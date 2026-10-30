<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Model\Product\PriceFinder;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Dotdigitalgroup\Email\Test\Unit\Traits\MockBuilderCompatibilityTrait;
use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\Bundle\Pricing\Adjustment\SelectionPriceListProviderInterface;
use Magento\Bundle\Pricing\Price\BundleRegularPrice;
use Magento\Bundle\Pricing\Price\BundleSelectionPrice;
use Magento\Bundle\Pricing\Price\FinalPrice as BundleFinalPrice;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\Amount\Base as AmountBase;
use Magento\Framework\Pricing\PriceInfo\Base;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Tax\Model\Config as TaxConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
class PriceFinderTest extends TestCase
{
    use MockBuilderCompatibilityTrait;

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
     * @var SelectionPriceListProviderInterface|MockObject
     */
    private $selectionPriceListProviderMock;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var PriceFinder
     */
    private $priceFinder;

    protected function setup(): void
    {
        $this->productMock = $this->getMockBuilder(
            $this->classWithAddedMethods(Product::class, ['getTaxClassId', 'getPriceType'])
        )
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getTypeInstance',
                    'getTypeId',
                    'getPrice',
                    'getSpecialPrice',
                    'getPriceInfo',
                    'getTaxClassId',
                    'getPriceType'
                ]
            )
            ->getMock();
        $this->baseMock = $this->createMock(Base::class);
        $this->bundleRegularPriceMock = $this->createMock(BundleRegularPrice::class);
        $this->amountBaseMock = $this->createMock(AmountBase::class);
        $this->configurableMock = $this->createMock(Configurable::class);
        $this->taxCalculatorMock = $this->createMock(TaxCalculator::class);
        $this->taxHelperMock = $this->createMock(TaxHelper::class);
        $this->selectionPriceListProviderMock = $this->createMock(SelectionPriceListProviderInterface::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->storeManagerMock->method('getStore')->willReturn($this->storeMock);

        $this->priceFinder = new PriceFinder(
            $this->taxCalculatorMock,
            $this->taxHelperMock,
            $this->selectionPriceListProviderMock,
            $this->storeManagerMock
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

    #[DataProvider('taxSettingsProvider')]
    public function testPricesFollowCatalogTaxSettingForAllProductTypes(
        string $typeId,
        ?int $bundlePriceType,
        bool $catalogPricesInclTax,
        int $displayType
    ) {
        $price = 100.00;
        $specialPrice = 50.00;
        $displayInclTax = $displayType !== TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX;

        $this->taxHelperMock->method('priceIncludesTax')->willReturn($catalogPricesInclTax);
        $taxCalculationMock = $this->createMock(TaxCalculationInterface::class);
        $taxCalculationMock->method('getCalculatedRate')->willReturn(20.0);
        $priceFinder = new PriceFinder(
            new TaxCalculator($taxCalculationMock, $this->taxHelperMock),
            $this->taxHelperMock,
            $this->selectionPriceListProviderMock,
            $this->storeManagerMock
        );

        $this->productMock->method('getTypeId')->willReturn($typeId);
        switch ($typeId) {
            case 'configurable':
            case 'grouped':
                $child = $this->createMock(Product::class);
                $child->method('getStoreIds')->willReturn([1]);
                $child->method('getPrice')->willReturn($price);
                $child->method('getSpecialPrice')->willReturn($specialPrice);
                $this->productMock->method('getTypeInstance')->willReturn($this->configurableMock);
                $this->configurableMock->method('getUsedProducts')->willReturn([$child]);
                $this->configurableMock->method('getAssociatedProducts')->willReturn([$child]);
                break;
            case 'bundle':
                $regularPriceMock = $this->createMock(BundleRegularPrice::class);
                $regularPriceMock->method('getMinimalPrice')
                    ->willReturn($this->getBundleAmount($price, $catalogPricesInclTax, $displayInclTax));
                $finalPriceMock = $this->createMock(BundleFinalPrice::class);
                $finalPriceMock->method('getMinimalPrice')
                    ->willReturn($this->getBundleAmount($specialPrice, $catalogPricesInclTax, $displayInclTax));
                $this->baseMock->method('getPrice')->willReturnMap([
                    ['regular_price', $regularPriceMock],
                    ['final_price', $finalPriceMock]
                ]);
                $this->productMock->method('getPriceInfo')->willReturn($this->baseMock);
                $this->productMock->method('getPriceType')->willReturn($bundlePriceType);
                $this->selectionPriceListProviderMock->method('getPriceList')->willReturnCallback(
                    fn ($product, $searchMin, $useRegularPrice) => [
                        $this->getSelection($useRegularPrice ? $price : $specialPrice, 2)
                    ]
                );
                break;
            default:
                $this->productMock->method('getPrice')->willReturn($price);
                $this->productMock->method('getSpecialPrice')->willReturn($specialPrice);
        }

        $expectedPriceInclTax = $catalogPricesInclTax ? $price : $price * 1.2;
        $expectedSpecialPriceInclTax = $catalogPricesInclTax ? $specialPrice : $specialPrice * 1.2;

        $this->assertEquals($price, $priceFinder->getPrice($this->productMock, 1));
        $this->assertEquals($specialPrice, $priceFinder->getSpecialPrice($this->productMock, 1));
        $this->assertEquals($expectedPriceInclTax, $priceFinder->getPriceInclTax($this->productMock, 1));
        $this->assertEquals($expectedSpecialPriceInclTax, $priceFinder->getSpecialPriceInclTax($this->productMock, 1));
    }

    public static function taxSettingsProvider(): array
    {
        $cases = [];
        $types = [
            'simple' => ['simple', null],
            'configurable' => ['configurable', null],
            'grouped' => ['grouped', null],
            'fixed bundle' => ['bundle', BundlePrice::PRICE_TYPE_FIXED],
            'dynamic bundle' => ['bundle', BundlePrice::PRICE_TYPE_DYNAMIC],
        ];
        $displayTypes = [
            'display excl' => TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX,
            'display incl' => TaxConfig::DISPLAY_TYPE_INCLUDING_TAX,
            'display both' => TaxConfig::DISPLAY_TYPE_BOTH,
        ];
        foreach ($types as $typeLabel => [$typeId, $bundlePriceType]) {
            foreach (['catalog excl' => false, 'catalog incl' => true] as $catalogLabel => $catalogPricesInclTax) {
                foreach ($displayTypes as $displayLabel => $displayType) {
                    $cases["$typeLabel, $catalogLabel, $displayLabel"] =
                        [$typeId, $bundlePriceType, $catalogPricesInclTax, $displayType];
                }
            }
        }
        return $cases;
    }

    #[DataProvider('mixedTaxClassBundleProvider')]
    public function testBundlePriceInclTaxMatchesStorefrontWithMixedSelectionTaxClasses(
        int $bundlePriceType,
        float $storefrontPriceInclTax
    ) {
        $this->taxHelperMock->method('priceIncludesTax')->willReturn(false);
        $taxCalculationMock = $this->createMock(TaxCalculationInterface::class);
        $taxCalculationMock->method('getCalculatedRate')
            ->willReturnCallback(fn ($taxClassId) => $taxClassId ? 8.25 : 0.0);
        $priceFinder = new PriceFinder(
            new TaxCalculator($taxCalculationMock, $this->taxHelperMock),
            $this->taxHelperMock,
            $this->selectionPriceListProviderMock,
            $this->storeManagerMock
        );

        $this->productMock->method('getTypeId')->willReturn('bundle');
        $this->productMock->method('getPriceType')->willReturn($bundlePriceType);
        $this->productMock->method('getTaxClassId')->willReturn(2);
        $this->productMock->method('getPriceInfo')->willReturn($this->baseMock);
        $this->baseMock->method('getPrice')->willReturn($this->bundleRegularPriceMock);
        $this->bundleRegularPriceMock->method('getMinimalPrice')
            ->willReturn(new AmountBase($storefrontPriceInclTax, ['tax' => $storefrontPriceInclTax - 61.00]));
        $this->selectionPriceListProviderMock->method('getPriceList')->willReturn([
            $this->getSelection(23.00, 2),
            $this->getSelection(5.00, 2),
            $this->getSelection(14.00, 2),
            $this->getSelection(19.00, 0),
        ]);

        $this->assertEquals(61.00, $priceFinder->getPrice($this->productMock, 1));
        $this->assertEquals($storefrontPriceInclTax, $priceFinder->getPriceInclTax($this->productMock, 1));
    }

    public static function mixedTaxClassBundleProvider(): array
    {
        return [
            'dynamic bundle taxes each selection' => [BundlePrice::PRICE_TYPE_DYNAMIC, 64.47],
            'fixed bundle uses the bundle tax class' => [BundlePrice::PRICE_TYPE_FIXED, 66.03],
        ];
    }

    public function testBundlePricesAreCalculatedInBaseCurrency()
    {
        $baseCurrency = $this->createMock(Currency::class);
        $displayCurrency = $this->createMock(Currency::class);
        $storeData = ['current_currency' => $displayCurrency];
        $this->storeMock->method('getBaseCurrency')->willReturn($baseCurrency);
        $this->storeMock->method('getData')->willReturnCallback(
            function ($key) use (&$storeData) {
                return $storeData[$key] ?? null;
            }
        );
        $this->storeMock->method('setData')->willReturnCallback(
            function ($key, $value) use (&$storeData) {
                $storeData[$key] = $value;
                return $this->storeMock;
            }
        );
        $currencyDuringCalculation = [];
        $trackCurrency = function ($value) use (&$storeData, &$currencyDuringCalculation) {
            $currencyDuringCalculation[] = $storeData['current_currency'];
            return $value;
        };

        $this->taxHelperMock->method('priceIncludesTax')->willReturn(false);
        $this->taxCalculatorMock->method('calculatePriceInclTax')->willReturnArgument(1);
        $this->productMock->method('getTypeId')->willReturn('bundle');
        $this->productMock->method('getPriceType')->willReturn(BundlePrice::PRICE_TYPE_DYNAMIC);
        $this->productMock->method('getPriceInfo')->willReturn($this->baseMock);
        $this->baseMock->method('getPrice')->willReturn($this->bundleRegularPriceMock);
        $this->bundleRegularPriceMock->method('getMinimalPrice')->willReturnCallback(
            fn () => $trackCurrency(new AmountBase(61.00))
        );
        $selection = $this->getSelection(61.00, 0, fn () => $trackCurrency(61.00));
        $this->selectionPriceListProviderMock->method('getPriceList')->willReturnCallback(
            fn () => $trackCurrency([$selection])
        );

        $this->assertEquals(61.00, $this->priceFinder->getPrice($this->productMock, 1));
        $this->assertEquals(61.00, $this->priceFinder->getPriceInclTax($this->productMock, 1));
        $this->assertCount(4, $currencyDuringCalculation);
        foreach ($currencyDuringCalculation as $currency) {
            $this->assertSame($baseCurrency, $currency);
        }
        $this->assertSame($displayCurrency, $storeData['current_currency']);
    }

    private function getSelection(float $value, int $taxClassId, ?callable $getValue = null): BundleSelectionPrice
    {
        $product = $this->getMockBuilder($this->classWithAddedMethods(Product::class, ['getTaxClassId']))
            ->disableOriginalConstructor()
            ->onlyMethods(['getTaxClassId'])
            ->getMock();
        $product->method('getTaxClassId')->willReturn($taxClassId);

        $selection = $this->createMock(BundleSelectionPrice::class);
        $selection->method('getValue')->willReturnCallback($getValue ?? fn () => $value);
        $selection->method('getQuantity')->willReturn(1.0);
        $selection->method('getProduct')->willReturn($product);

        return $selection;
    }

    /**
     * Mirrors Magento's bundle amount: tax is added when catalog prices include tax or are displayed incl. tax.
     */
    private function getBundleAmount(float $catalogPrice, bool $catalogPricesInclTax, bool $displayInclTax): AmountBase
    {
        $exclTax = $catalogPricesInclTax ? $catalogPrice / 1.2 : $catalogPrice;
        $amount = ($catalogPricesInclTax || $displayInclTax) ? $exclTax * 1.2 : $exclTax;

        return new AmountBase($amount, ['tax' => $amount - $exclTax]);
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
