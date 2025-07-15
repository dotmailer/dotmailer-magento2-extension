<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Tax;

use Magento\Catalog\Model\Product;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data as TaxHelper;

class TaxCalculator
{
    /**
     * @var TaxCalculationInterface
     */
    private $taxCalculation;

    /**
     * @var TaxHelper
     */
    private $taxHelper;

    /**
     * @param TaxCalculationInterface $taxCalculation
     * @param TaxHelper $taxHelper
     */
    public function __construct(
        TaxCalculationInterface $taxCalculation,
        TaxHelper $taxHelper
    ) {
        $this->taxCalculation = $taxCalculation;
        $this->taxHelper = $taxHelper;
    }

    /**
     * Calculate price including tax
     *
     * @param Product $product
     * @param float $price
     * @param int|null $storeId
     * @param int|null $customerId
     *
     * @return float
     */
    public function calculatePriceInclTax(Product $product, float $price, ?int $storeId, ?int $customerId = null): float
    {
        if ($this->taxHelper->priceIncludesTax($storeId)) {
            return $price;
        }

        $rate = $this->taxCalculation->getCalculatedRate(
            $product->getTaxClassId(),
            $customerId,
            $storeId
        );

        return $this->adjustPricesWithTaxes($price, $rate);
    }

    /**
     * Adjust prices with taxes.
     *
     * @param float $price
     * @param float $taxRate
     *
     * @return float
     */
    private function adjustPricesWithTaxes(float $price, float $taxRate): float
    {
        return $price + ($price * ($taxRate / 100));
    }
}
