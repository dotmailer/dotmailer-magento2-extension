<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Model\Customer\GroupCustomerFinder;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Exception\LocalizedException;

class RulePriceFinder
{
    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * RulePriceFinder constructor.
     *
     * @param TaxCalculator $taxCalculator
     */
    public function __construct(
        TaxCalculator $taxCalculator
    ) {
        $this->taxCalculator = $taxCalculator;
    }

    /**
     * Adds rules pricing array.
     *
     * @param MagentoProduct $product
     * @param int|null $storeId
     *
     * @return array
     */
    public function getRulePrices(MagentoProduct $product, ?int $storeId): array
    {
        $data = $product->getData();
        $rulePricingProperties = array_filter($data, function ($key) {
            return strpos($key, 'rule_pricing') === 0;
        }, ARRAY_FILTER_USE_KEY);

        $rulePrices = [];
        foreach ($rulePricingProperties as $key => $price) {
            if (strpos($key, 'group_name') !== false) {
                continue;
            }
            $groupId = str_replace('rule_pricing_', '', $key);
            $rulePrices[] = [
                'customer_group' => $rulePricingProperties['rule_pricing_group_name_'.$groupId] ?? '',
                'final_price' => floatval($price),
                'final_price_incl_tax' => $this->taxCalculator->calculatePriceInclTax(
                    $product,
                    floatval($price),
                    $storeId
                )
            ];
        }
        return $rulePrices;
    }
}
