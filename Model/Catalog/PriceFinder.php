<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class PriceFinder
{
    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

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
     * @param CatalogHelper $catalogHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CatalogHelper $catalogHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * Get price.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return float
     */
    public function getPrice(Product $product, ?int $storeId): float
    {
        if (!isset($this->prices)) {
            $this->setPrices($product, $storeId);
        }
        return $this->prices['price'] ?? 0.00;
    }

    /**
     * Get special price.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return float
     */
    public function getSpecialPrice(Product $product, ?int $storeId): float
    {
        if (!isset($this->prices)) {
            $this->setPrices($product, $storeId);
        }
        return $this->prices['specialPrice'] ?? 0.00;
    }

    /**
     * Get price including tax.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return float
     */
    public function getPriceInclTax(Product $product, ?int $storeId): float
    {
        if (!isset($this->pricesInclTax)) {
            $this->setPricesInclTax($product, $storeId);
        }
        return $this->pricesInclTax['price'] ?? 0.00;
    }

    /**
     * Get special price including tax.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return float
     */
    public function getSpecialPriceInclTax(Product $product, ?int $storeId): float
    {
        if (!isset($this->pricesInclTax)) {
            $this->setPricesInclTax($product, $storeId);
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
            $configurableProductInstance = $product->getTypeInstance();
            /** @var Configurable $configurableProductInstance */
            $childProducts = $configurableProductInstance->getUsedProducts($product);
            /** @var Product $childProduct */
            foreach ($childProducts as $childProduct) {
                if ($storeId && !in_array($storeId, $childProduct->getStoreIds())) {
                    continue;
                }
                $childPrices[] = $childProduct->getPrice();
                if ($childProduct->getSpecialPrice() !== null) {
                    $childSpecialPrices[] = $childProduct->getSpecialPrice();
                }
            }
            $price = isset($childPrices) ? min($childPrices) : null;
            $specialPrice = isset($childSpecialPrices) ? min($childSpecialPrices) : null;
        } elseif ($product->getTypeId() == 'bundle') {
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price');
            /** @var \Magento\Bundle\Pricing\Price\BundleRegularPrice $regularPrice */
            $price = $regularPrice->getMinimalPrice()->getValue();

            $finalPrice = $product->getPriceInfo()->getPrice('final_price');
            /** @var \Magento\Bundle\Pricing\Price\FinalPrice $finalPrice */
            $specialPrice = $finalPrice->getMinimalPrice()->getValue();

            // if special price equals to price then it's wrong.
            $specialPrice = ($specialPrice === $price) ? null : $specialPrice;
        } elseif ($product->getTypeId() == 'grouped') {
            foreach ($product->getTypeInstance()->getAssociatedProducts($product) as $childProduct) {
                $childPrices[] = $childProduct->getPrice();
                if ($childProduct->getSpecialPrice() !== null) {
                    $childSpecialPrices[] = $childProduct->getSpecialPrice();
                }
            }
            $price = isset($childPrices) ? min($childPrices) : null;
            $specialPrice = isset($childSpecialPrices) ? min($childSpecialPrices) : null;
        } else {
            $price = $product->getPrice();
            $specialPrice = $product->getSpecialPrice();
        }
        $this->prices['price'] = $this->formatPriceValue($price);
        $this->prices['specialPrice'] = $this->formatPriceValue($specialPrice);
    }

    /**
     * Set prices including tax.
     * - In catalog sync, the rate is based on the (scoped) tax origin, as configured in
     * Default Tax Destination Calculation > Default Country.
     * - If the prices are already including tax, we just set them to the existing price and
     * special price values.
     *
     * @param Product $product
     * @param string|int|null $storeId
     * @return void
     */
    private function setPricesInclTax(Product $product, $storeId)
    {
        $price = $this->getPrice($product, $storeId);
        $specialPrice = $this->getSpecialPrice($product, $storeId);

        $this->pricesInclTax['price'] = $this->getTaxCalculatedPrice($product, $price);
        $this->pricesInclTax['specialPrice'] = $this->getTaxCalculatedPrice($product, $specialPrice);
    }

    /**
     * Get the tax calculated price of a product.
     *
     * This method uses the taxHelper to calculate the tax price for a given product and price.
     *
     * @param ProductInterface $product The product for which the tax is to be calculated.
     * @param float $price The price to which the tax is to be applied.
     *
     * @return float
     */
    private function getTaxCalculatedPrice(ProductInterface $product, float $price): float
    {
        try {
            $store = $this->storeManager->getStore();
        } catch (NoSuchEntityException $e) {
            $store = null;
        }

        return $this->catalogHelper->getTaxPrice(
            $product,
            $price,
            null,
            null,
            null,
            null,
            $store
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
