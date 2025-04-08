<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Tax;

use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Catalog\Model\Product;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use PHPUnit\Framework\TestCase;

class TaxCalculatorTest extends TestCase
{
    /**
     * @var TaxCalculationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taxCalculationMock;

    /**
     * @var TaxHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taxHelperMock;

    /**
     * @var Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productMock;

    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    protected function setUp(): void
    {
        $this->taxCalculationMock = $this->createMock(TaxCalculationInterface::class);
        $this->taxHelperMock = $this->createMock(TaxHelper::class);
        $this->productMock = $this->createMock(Product::class);

        $this->taxCalculator = new TaxCalculator(
            $this->taxCalculationMock,
            $this->taxHelperMock
        );
    }

    public function testCalculatePriceInclTaxWhenPriceIncludesTax()
    {
        $price = 100.00;
        $storeId = 1;

        $this->taxHelperMock->expects($this->once())
            ->method('priceIncludesTax')
            ->with($storeId)
            ->willReturn(true);

        $result = $this->taxCalculator->calculatePriceInclTax($this->productMock, $price, $storeId);

        $this->assertEquals($price, $result);
    }

    public function testCalculatePriceInclTaxWhenPriceExcludesTax()
    {
        $price = 100.00;
        $storeId = 1;
        $customerId = 1;
        $taxRate = 20.0;
        $expectedPriceInclTax = 120.00;

        $this->taxHelperMock->expects($this->once())
            ->method('priceIncludesTax')
            ->with($storeId)
            ->willReturn(false);

        $this->productMock->expects($this->once())
            ->method('__call')
            ->with('getTaxClassId')
            ->willReturn(2);

        $this->taxCalculationMock->expects($this->once())
            ->method('getCalculatedRate')
            ->with(2, $customerId, $storeId)
            ->willReturn($taxRate);

        $result = $this->taxCalculator->calculatePriceInclTax($this->productMock, $price, $storeId, $customerId);

        $this->assertEquals($expectedPriceInclTax, $result);
    }
}
