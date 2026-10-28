<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\Product\PriceFinderInterface;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\Bundle\Pricing\Adjustment\SelectionPriceListProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data as TaxHelper;

class PriceFinder implements PriceFinderInterface
{
    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * @var TaxHelper
     */
    private $taxHelper;

    /**
     * @var SelectionPriceListProviderInterface
     */
    private $selectionPriceListProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @param TaxHelper $taxHelper
     * @param SelectionPriceListProviderInterface $selectionPriceListProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        TaxCalculator $taxCalculator,
        TaxHelper $taxHelper,
        SelectionPriceListProviderInterface $selectionPriceListProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->taxCalculator = $taxCalculator;
        $this->taxHelper = $taxHelper;
        $this->selectionPriceListProvider = $selectionPriceListProvider;
        $this->storeManager = $storeManager;
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
            // Bundle amounts include tax if displayed incl. tax, so strip it when catalog prices exclude tax.
            $exclude = $this->taxHelper->priceIncludesTax($storeId) ? null : 'tax';

            [$price, $specialPrice] = $this->executeInBaseCurrency(function () use ($product, $exclude) {
                $regularPrice = $product->getPriceInfo()->getPrice('regular_price');
                /** @var \Magento\Bundle\Pricing\Price\BundleRegularPrice $regularPrice */
                $finalPrice = $product->getPriceInfo()->getPrice('final_price');
                /** @var \Magento\Bundle\Pricing\Price\FinalPrice $finalPrice */
                return [
                    $regularPrice->getMinimalPrice()->getValue($exclude),
                    $finalPrice->getMinimalPrice()->getValue($exclude)
                ];
            });

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

        if ($product->getTypeId() == 'bundle' && $product->getPriceType() == BundlePrice::PRICE_TYPE_DYNAMIC) {
            $this->pricesInclTax['price'] = $this->formatPriceValue(
                $this->getDynamicBundlePriceInclTax($product, $price, true, $storeId, $customerId)
            );
            $this->pricesInclTax['specialPrice'] = $specialPrice ? $this->formatPriceValue(
                $this->getDynamicBundlePriceInclTax($product, $specialPrice, false, $storeId, $customerId)
            ) : 0.00;
            return;
        }

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
     * Get dynamic bundle price including tax.
     *
     * Like Magento, each selection is taxed with its own tax class and rounded, and any
     * remaining base price is taxed with the bundle's tax class.
     *
     * @param Product $product
     * @param float $price
     * @param bool $useRegularPrice
     * @param int|null $storeId
     * @param int|null $customerId
     *
     * @return float
     */
    private function getDynamicBundlePriceInclTax(
        Product $product,
        float $price,
        bool $useRegularPrice,
        ?int $storeId,
        ?int $customerId
    ): float {

        return $this->executeInBaseCurrency(
            function () use ($product, $price, $useRegularPrice, $storeId, $customerId) {
                $basePrice = $price;
                $priceInclTax = 0.0;
                $selections = $this->selectionPriceListProvider->getPriceList($product, true, $useRegularPrice);
                foreach ($selections as $selection) {
                    $value = (float) $selection->getValue();
                    $qty = (float) $selection->getQuantity();
                    $basePrice -= round($value, 2) * $qty;
                    $priceInclTax += round(
                        $this->taxCalculator->calculatePriceInclTax(
                            $selection->getProduct(),
                            $value,
                            $storeId,
                            $customerId
                        ),
                        2
                    ) * $qty;
                }

                return $priceInclTax
                    + $this->taxCalculator->calculatePriceInclTax($product, $basePrice, $storeId, $customerId);
            }
        );
    }

    /**
     * Execute a callback with the current store's currency set to its base currency.
     *
     * Magento's price framework converts amounts into the store's current (display) currency,
     * whereas catalog sync sends all prices in the base currency.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    private function executeInBaseCurrency(callable $callback)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $currentCurrency = $store->getData('current_currency');
        $store->setData('current_currency', $store->getBaseCurrency());

        try {
            return $callback();
        } finally {
            $store->setData('current_currency', $currentCurrency);
        }
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
