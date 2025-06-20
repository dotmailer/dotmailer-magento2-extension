<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Model\Connector\ContactData\CustomerGroupLoader;
use Dotdigitalgroup\Email\Model\Product\TierPriceFinder;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TierPriceFinderTest extends TestCase
{
    /**
     * @var TierPriceFinder
     */
    private $tierPriceFinder;

    /**
     * @var CustomerGroupLoader|MockObject
     */
    private $customerGroupLoader;

    /**
     * @var TaxCalculator|MockObject
     */
    private $taxCalculator;

    /**
     * @var ProductRepository|MockObject
     */
    private $productRepository;

    protected function setUp(): void
    {
        $this->customerGroupLoader = $this->createMock(CustomerGroupLoader::class);
        $this->taxCalculator = $this->createMock(TaxCalculator::class);
        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->tierPriceFinder = new TierPriceFinder(
            $this->customerGroupLoader,
            $this->taxCalculator,
            $this->productRepository
        );
    }

    public function testGetTierPricesForSimpleProduct()
    {
        $storeId = 1;
        $customerGroupId = null;
        $price = 10.00;
        $priceInclTax = 12.00;
        $quantity = 5;

        $product = $this->createMock(Product::class);
        $tierPrice = $this->createMock(ProductTierPriceInterface::class);
        $extensionAttributes = $this->getMockBuilder(ProductTierPriceExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPercentageValue'])
            ->getMock();

        $product->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $product->expects($this->once())
            ->method('getTierPrices')
            ->willReturn([$tierPrice]);

        $tierPrice->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);

        $tierPrice->expects($this->once())
            ->method('getValue')
            ->willReturn($price);

        $tierPrice->expects($this->once())
            ->method('getQty')
            ->willReturn($quantity);

        $tierPrice->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $extensionAttributes->expects($this->once())
            ->method('getPercentageValue')
            ->willReturn(null);

        $this->customerGroupLoader->expects($this->once())
            ->method('getCustomerGroup')
            ->with($customerGroupId)
            ->willReturn('ALL GROUPS');

        $this->taxCalculator->expects($this->once())
            ->method('calculatePriceInclTax')
            ->with($product, $price, $storeId)
            ->willReturn($priceInclTax);

        $result = $this->tierPriceFinder->getTierPricesByStoreAndGroup(
            $product,
            $storeId,
            $customerGroupId
        );

        $this->assertEquals([
            'customer_group' => 'ALL GROUPS',
            'price' => 10.00,
            'price_incl_tax' => 12.00,
            'quantity' => 5,
            'percentage' => null,
            'type' => 'Fixed Price'
        ], $result[0]);
    }

    public function testGetTierPricesForSimpleProductForOneCustomerGroup()
    {
        $storeId = 1;
        $customerGroupId = 1;
        $price = 10.00;
        $priceInclTax = 12.00;
        $quantity = 5;

        $product = $this->createMock(Product::class);
        $tierPrice = $this->createMock(ProductTierPriceInterface::class);
        $extensionAttributes = $this->getMockBuilder(ProductTierPriceExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPercentageValue'])
            ->getMock();

        $product->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $product->expects($this->once())
            ->method('getTierPrices')
            ->willReturn([$tierPrice]);

        $tierPrice->expects($this->exactly(2))
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);

        $tierPrice->expects($this->once())
            ->method('getValue')
            ->willReturn($price);

        $tierPrice->expects($this->once())
            ->method('getQty')
            ->willReturn($quantity);

        $tierPrice->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $extensionAttributes->expects($this->once())
            ->method('getPercentageValue')
            ->willReturn(null);

        $this->customerGroupLoader->expects($this->once())
            ->method('getCustomerGroup')
            ->with($customerGroupId)
            ->willReturn('General');

        $this->taxCalculator->expects($this->once())
            ->method('calculatePriceInclTax')
            ->with($product, $price, $storeId)
            ->willReturn($priceInclTax);

        $result = $this->tierPriceFinder->getTierPricesByStoreAndGroup(
            $product,
            $storeId,
            $customerGroupId
        );

        $this->assertEquals([
            'customer_group' => 'General',
            'price' => 10.00,
            'price_incl_tax' => 12.00,
            'quantity' => 5,
            'percentage' => null,
            'type' => 'Fixed Price'
        ], $result[0]);
    }

    public function testGetTierPricesForConfigurableProduct()
    {
        $storeId = 1;
        $customerGroupId = null;
        $quantity = 3;

        $childProduct1TierPrices = [
            [
                'customer_group' => 'General',
                'price' => 12.00,
                'price_incl_tax' => 18.00,
                'quantity' => 3,
                'percentage' => null,
                'type' => 'Fixed Price'
            ],
            [
                'customer_group' => 'Chaz',
                'price' => 12.00,
                'price_incl_tax' => 18.00,
                'quantity' => 3,
                'percentage' => null,
                'type' => 'Fixed Price'
            ]
        ];
        $childProduct2TierPrices = [
            [
                'customer_group' => 'General',
                'price' => 15.00,
                'price_incl_tax' => 18.00,
                'quantity' => 3,
                'percentage' => null,
                'type' => 'Fixed Price'
            ],
            [
                'customer_group' => 'Chaz',
                'price' => 11.00,
                'price_incl_tax' => 18.00,
                'quantity' => 3,
                'percentage' => null,
                'type' => 'Fixed Price'
            ]
        ];

        $configurableProduct = $this->createMock(Product::class);
        $configurableProductInstance = $this->createMock(Configurable::class);
        $childProduct = $this->createMock(Product::class);
        $tierPrice = $this->createMock(ProductTierPriceInterface::class);
        $extensionAttributes = $this->getMockBuilder(ProductTierPriceExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPercentageValue'])
            ->getMock();

        $configurableProduct->expects($this->once())
            ->method('getTypeId')
            ->willReturn('configurable');

        $configurableProduct->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($configurableProductInstance);

        $configurableProductInstance->expects($this->once())
            ->method('getUsedProducts')
            ->with($configurableProduct)
            ->willReturnOnConsecutiveCalls([$childProduct, $childProduct]);

        $childProduct->expects($this->exactly(2))
            ->method('getStoreIds')
            ->willReturn([$storeId]);

        $childProduct->expects($this->exactly(2))
            ->method('getTierPrices')
            ->willReturnOnConsecutiveCalls([$tierPrice, $tierPrice], [$tierPrice, $tierPrice]);

        $tierPrice->expects($this->exactly(4))
            ->method('getCustomerGroupId')
            ->willReturnOnConsecutiveCalls(null, 1, null, 1);

        $tierPrice->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $childProduct1TierPrices[0]['price'],
                $childProduct1TierPrices[1]['price'],
                $childProduct2TierPrices[0]['price'],
                $childProduct2TierPrices[1]['price']
            );

        $tierPrice->expects($this->exactly(4))
            ->method('getQty')
            ->willReturn($quantity);

        $tierPrice->expects($this->exactly(4))
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $extensionAttributes->expects($this->exactly(4))
            ->method('getPercentageValue')
            ->willReturn(null);

        $this->customerGroupLoader->expects($this->exactly(4))
            ->method('getCustomerGroup')
            ->willReturnOnConsecutiveCalls(
                'NOT LOGGED IN',
                'Chaz',
                'NOT LOGGED IN',
                'Chaz'
            );

        $matcher = $this->exactly(4);
        $this->taxCalculator->expects($matcher)
            ->method('calculatePriceInclTax')
            ->willReturnCallback(function () use (
                $matcher,
                $storeId,
                $childProduct,
                $childProduct1TierPrices,
                $childProduct2TierPrices,
            ) {
                return match ($matcher->getInvocationCount()) {
                    1 => [$childProduct, $childProduct1TierPrices[0]['price'], $storeId],
                    2 => [$childProduct, $childProduct1TierPrices[1]['price'], $storeId],
                    3 => [$childProduct, $childProduct2TierPrices[0]['price'], $storeId],
                    4 => [$childProduct, $childProduct2TierPrices[1]['price'], $storeId],
                };
            })
            ->willReturnOnConsecutiveCalls(
                $childProduct1TierPrices[0]['price_incl_tax'],
                $childProduct1TierPrices[1]['price_incl_tax'],
                $childProduct2TierPrices[0]['price_incl_tax'],
                $childProduct2TierPrices[1]['price_incl_tax']
            );

        $result = $this->tierPriceFinder->getTierPricesByStoreAndGroup(
            $configurableProduct,
            $storeId,
            $customerGroupId
        );

        $this->assertEquals([
            [
                'customer_group' => 'NOT LOGGED IN',
                'price' => 12.00,
                'price_incl_tax' => 18.00,
                'quantity' => 3,
                'percentage' => null,
                'type' => 'Fixed Price'
            ],
            [
                'customer_group' => 'Chaz',
                'price' => 11.00,
                'price_incl_tax' => 18.00,
                'quantity' => 3,
                'percentage' => null,
                'type' => 'Fixed Price'
            ]
        ], $result);
    }

    public function testGetTierPricesForBundleProduct()
    {
        $storeId = 1;
        $customerGroupId = 1;
        $parentPrice = 20.00;
        $parentPriceInclTax = 24.00;
        $childPrice = 10.00;
        $childPriceInclTax = 12.00;
        $quantity = 2;

        $bundleProduct = $this->createMock(Product::class);
        $simpleProduct = $this->createMock(Product::class);
        $bundleProductInstance = $this->createMock(\Magento\Bundle\Model\Product\Type::class);
        $tierPrice = $this->createMock(ProductTierPriceInterface::class);
        $extensionAttributes = $this->getMockBuilder(ProductTierPriceExtensionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getPercentageValue'])
            ->getMock();

        $bundleProduct->expects($this->once())
            ->method('getTypeId')
            ->willReturn('bundle');

        $bundleProduct->expects($this->once())
            ->method('getTypeInstance')
            ->willReturn($bundleProductInstance);

        $bundleProductInstance->expects($this->once())
            ->method('getChildrenIds')
            ->willReturn([1 => [1]]);

        $bundleProduct->expects($this->once())
            ->method('getTierPrices')
            ->willReturn([$tierPrice]);

        $tierPrice->expects($this->exactly(2))
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);

        $tierPrice->expects($this->once())
            ->method('getValue')
            ->willReturn($parentPrice);

        $tierPrice->expects($this->once())
            ->method('getQty')
            ->willReturn($quantity);

        $tierPrice->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $extensionAttributes->expects($this->once())
            ->method('getPercentageValue')
            ->willReturn(20);

        $this->customerGroupLoader->expects($this->once())
            ->method('getCustomerGroup')
            ->with($customerGroupId)
            ->willReturn('General');

        $matcher = $this->exactly(2);
        $this->taxCalculator->expects($matcher)
            ->method('calculatePriceInclTax')
            ->willReturnCallback(function () use (
                $matcher,
                $bundleProduct,
                $parentPrice,
                $storeId,
                $simpleProduct,
                $childPrice
            ) {
                return match ($matcher->getInvocationCount()) {
                    1 => [$bundleProduct, $parentPrice, $storeId],
                    2 => [$simpleProduct, $childPrice, $storeId],
                };
            })
            ->willReturnOnConsecutiveCalls($parentPriceInclTax, $childPriceInclTax);

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($simpleProduct);

        $simpleProduct->expects($this->exactly(2))
            ->method('getPrice')
            ->willReturn($childPrice);

        $result = $this->tierPriceFinder->getTierPricesByStoreAndGroup(
            $bundleProduct,
            $storeId,
            $customerGroupId
        );

        $this->assertEquals([
            'customer_group' => 'General',
            'price' => 8.00,
            'price_incl_tax' => 12.00,
            'quantity' => 2,
            'percentage' => 20,
            'type' => 'Percentage Discount'
        ], $result[0]);
    }
}
