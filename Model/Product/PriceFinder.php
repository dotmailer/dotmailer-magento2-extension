<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\Product\PriceFinderInterface;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class PriceFinder implements PriceFinderInterface
{
    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * @var array
     */
    private $prices;

    /**
     * @var array
     */
    private $pricesInclTax;

    /**
     * @var Product
     */
    private $childProductWithLowestPrice;

    /**
     * @var Product
     */
    private $childProductWithLowestSpecialPrice;

    /**
     * @param TaxCalculator $taxCalculator
     */
    public function __construct(
        TaxCalculator $taxCalculator
    ) {
        $this->taxCalculator = $taxCalculator;
    }

    /**
     * @inheritDoc
     */
    public function getPrice(Product $product, ?int $storeId): float
    {
        if (!isset($this->prices)) {
            $this->setPrices($product, $storeId);
        }
        return $this->prices['price'] ?? 0.00;
    }

    /**
     * @inheritDoc
     */
    public function getSpecialPrice(Product $product, ?int $storeId): float
    {
        if (!isset($this->prices)) {
            $this->setPrices($product, $storeId);
        }
        return $this->prices['specialPrice'] ?? 0.00;
    }

    /**
     * @inheritDoc
     */
    public function getPriceInclTax(Product $product, ?int $storeId, ?int $customerId = null): float
    {
        if (!isset($this->pricesInclTax)) {
            $this->setPricesInclTax($product, $storeId, $customerId);
        }
        return $this->pricesInclTax['price'] ?? 0.00;
    }

    /**
     * @inheritDoc
     */
    public function getSpecialPriceInclTax(Product $product, ?int $storeId, ?int $customerId = null): float
    {
        if (!isset($this->pricesInclTax)) {
            $this->setPricesInclTax($product, $storeId, $customerId);
        }
        return $this->pricesInclTax['specialPrice'] ?? 0.00;
    }

    /**
     * Set prices for all product types.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return void
     */
    private function setPrices(Product $product, ?int $storeId)
    {
        if ($product->getTypeId() == 'configurable') {
            /** @var Configurable $configurableProductInstance */
            $configurableProductInstance = $product->getTypeInstance();
            $childProducts = $configurableProductInstance->getUsedProducts($product);
            $price = null;
            $specialPrice = null;

            /** @var Product $childProduct */
            foreach ($childProducts as $childProduct) {
                if ($storeId && !in_array($storeId, $childProduct->getStoreIds())) {
                    continue;
                }
                $childPrice = $childProduct->getPrice();
                $childSpecialPrice = $childProduct->getSpecialPrice();
                if ($price === null || $childPrice < $price) {
                    $this->childProductWithLowestPrice = $childProduct;
                    $price = $childPrice;
                }
                if ($specialPrice === null || (!empty($childSpecialPrice) && $childSpecialPrice < $specialPrice)) {
                    $this->childProductWithLowestSpecialPrice = $childProduct;
                    $specialPrice = $childSpecialPrice;
                }
            }
        } elseif ($product->getTypeId() == 'grouped') {
            $childProducts = $product->getTypeInstance()->getAssociatedProducts($product);
            $price = null;
            $specialPrice = null;

            foreach ($childProducts as $childProduct) {
                $childPrice = $childProduct->getPrice();
                $childSpecialPrice = $childProduct->getSpecialPrice();
                if ($price === null || $childPrice < $price) {
                    $this->childProductWithLowestPrice = $childProduct;
                    $price = $childPrice;
                }
                if ($specialPrice === null || (!empty($childSpecialPrice) && $childSpecialPrice < $specialPrice)) {
                    $this->childProductWithLowestSpecialPrice = $childProduct;
                    $specialPrice = $childSpecialPrice;
                }
            }
        } elseif ($product->getTypeId() == 'bundle') {
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price');
            /** @var \Magento\Bundle\Pricing\Price\BundleRegularPrice $regularPrice */
            $price = $regularPrice->getMinimalPrice()->getValue();

            $finalPrice = $product->getPriceInfo()->getPrice('final_price');
            /** @var \Magento\Bundle\Pricing\Price\FinalPrice $finalPrice */
            $specialPrice = $finalPrice->getMinimalPrice()->getValue();

            // if special price equals to price then it's wrong.
            $specialPrice = ($specialPrice === $price) ? null : $specialPrice;
        } else {
            $price = $product->getPrice();
            $specialPrice = $product->getSpecialPrice();
        }
        $this->prices['price'] = $this->formatPriceValue($price);
        $this->prices['specialPrice'] = $this->formatPriceValue($specialPrice);
    }

    /**
     * Set prices including tax.
     *
     * If prices include tax in the catalog, we can use the prices directly.
     * If prices exclude tax in the catalog, we need to calculate the prices including tax.
     * $customerId is passed in from storefront view models, left null for catalog sync.
     *
     * @param Product $product
     * @param int|null $storeId
     * @param int|null $customerId
     *
     * @return void
     */
    private function setPricesInclTax(Product $product, ?int $storeId, ?int $customerId = null): void
    {
        $price = $this->getPrice($product, $storeId);
        $specialPrice = $this->getSpecialPrice($product, $storeId);

        if (isset($this->childProductWithLowestPrice)) {
            $product = $this->childProductWithLowestPrice;
        }

        $this->pricesInclTax['price'] = $this->formatPriceValue(
            $this->taxCalculator->calculatePriceInclTax(
                $product,
                $price,
                $storeId,
                $customerId
            )
        );

        if (isset($this->childProductWithLowestSpecialPrice)) {
            $product = $this->childProductWithLowestSpecialPrice;
        }

        $this->pricesInclTax['specialPrice'] = $this->formatPriceValue(
            $this->taxCalculator->calculatePriceInclTax(
                $product,
                $specialPrice,
                $storeId,
                $customerId
            )
        );
    }

    /**
     * Formats a price value.
     *
     * @param string|float|null $price
     *
     * @return float
     */
    private function formatPriceValue($price): float
    {
        return (float) number_format(
            (float) $price,
            2,
            '.',
            ''
        );
    }
}
