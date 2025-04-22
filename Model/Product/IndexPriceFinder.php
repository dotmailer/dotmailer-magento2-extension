<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Model\Customer\GroupCustomerFinder;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Exception\LocalizedException;

class IndexPriceFinder
{
    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * IndexPriceFinder constructor.
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
    public function getIndexPrices(MagentoProduct $product, ?int $storeId): array
    {
        $data = $product->getData();
        $indexPricingProperties = $this->arrangeIndexPricesByGroupSuffix($data);

        $indexPrices = [];
        foreach ($indexPricingProperties as $group) {
            $price = floatval($group['index_pricing_price'] ?? 0);
            $finalPrice = floatval($group['index_pricing_final_price'] ?? 0);
            $minPrice = floatval($group['index_pricing_min_price'] ?? 0);
            $maxPrice = floatval($group['index_pricing_max_price'] ?? 0);
            $tierPrice = floatval($group['index_pricing_tier_price'] ?? 0);

            $indexPrices[] = [
                'customer_group' => $group['index_pricing_group_name'] ?? '',
                'price' => $price,
                'price_incl_tax' => $this->taxCalculator->calculatePriceInclTax(
                    $product,
                    $price,
                    $storeId
                ),
                'final_price' => $finalPrice,
                'final_price_incl_tax' => $this->taxCalculator->calculatePriceInclTax(
                    $product,
                    $finalPrice,
                    $storeId
                ),
                'min_price' => $minPrice,
                'min_price_incl_tax' => $this->taxCalculator->calculatePriceInclTax(
                    $product,
                    $minPrice,
                    $storeId
                ),
                'max_price' => $maxPrice,
                'max_price_incl_tax' => $this->taxCalculator->calculatePriceInclTax(
                    $product,
                    $maxPrice,
                    $storeId
                ),
                'tier_price' => $tierPrice,
                'tier_price_incl_tax' => $this->taxCalculator->calculatePriceInclTax(
                    $product,
                    $tierPrice,
                    $storeId
                ),
            ];
        }
        return $indexPrices;
    }

    /**
     * Arrange index prices by group suffix.
     *
     * @param array $data
     *
     * @return array
     */
    private function arrangeIndexPricesByGroupSuffix(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (strpos($key, 'index_pricing') !== 0) {
                continue;
            }
            // Extract the numeric suffix from the key
            if (preg_match('/_(\d+)$/', $key, $matches)) {
                $suffix = $matches[1];

                // Initialize the sub-array if it doesn't exist
                if (!isset($result[$suffix])) {
                    $result[$suffix] = [];
                    $result[$suffix]['group_id'] = $suffix;
                }

                // Remove the suffix from the key
                $trimmedKey = preg_replace('/_\d+$/', '', $key);

                // Add the key-value pair to the corresponding suffix group
                $result[$suffix][$trimmedKey] = $value;
            }
        }

        return $result;
    }
}
