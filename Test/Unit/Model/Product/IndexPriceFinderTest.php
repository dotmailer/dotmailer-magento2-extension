<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Model\Product\IndexPriceFinder;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Catalog\Model\Product as MagentoProduct;
use PHPUnit\Framework\TestCase;

class IndexPriceFinderTest extends TestCase
{
    /**
     * @var TaxCalculator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taxCalculatorMock;

    /**
     * @var IndexPriceFinder
     */
    private $indexPriceFinder;

    protected function setUp(): void
    {
        $this->taxCalculatorMock = $this->createMock(TaxCalculator::class);
        $this->indexPriceFinder = new IndexPriceFinder($this->taxCalculatorMock);
    }

    public function testGetIndexPrices()
    {
        $productMock = $this->createMock(MagentoProduct::class);

        $productMock->expects($this->once())
            ->method('getData')
            ->willReturn([
                'index_pricing_price_1' => 10.00,
                'index_pricing_final_price_1' => 12.00,
                'index_pricing_min_price_1' => 8.00,
                'index_pricing_max_price_1' => 15.00,
                'index_pricing_tier_price_1' => 9.00,
                'index_pricing_group_name_1' => 'General',
            ]);

        $matcher = $this->exactly(5);
        $this->taxCalculatorMock->expects($matcher)
            ->method('calculatePriceInclTax')
            ->willReturnCallback(function () use ($matcher, $productMock) {
                return match ($matcher->numberOfInvocations()) {
                    0 => [$productMock, 10.00, null],
                    1 => [$productMock, 12.00, null],
                    2 => [$productMock, 8.00, null],
                    3 => [$productMock, 15.00, null],
                    4 => [$productMock, 9.00, null],
                };
            })
            ->willReturnOnConsecutiveCalls(12.00, 14.40, 9.60, 18.00, 10.80);

        $result = $this->indexPriceFinder->getIndexPrices($productMock, null);

        $expected = [
            [
                'customer_group' => 'General',
                'price' => 10.00,
                'price_incl_tax' => 12.00,
                'final_price' => 12.00,
                'final_price_incl_tax' => 14.40,
                'min_price' => 8.00,
                'min_price_incl_tax' => 9.60,
                'max_price' => 15.00,
                'max_price_incl_tax' => 18.00,
                'tier_price' => 9.00,
                'tier_price_incl_tax' => 10.80,
            ]
        ];

        $this->assertEquals($expected, $result);
    }
}
