<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Catalog;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Catalog\Model\ProductRenderFactory;
use Magento\Catalog\Ui\DataProvider\Product\ProductRenderCollectorComposite;
use Magento\Framework\EntityManager\Hydrator;
use Magento\Tax\Api\TaxCalculationInterface;

class PriceFinder
{
    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * @var TaxCalculationInterface
     */
    private $taxCalculation;

    /**
     * @var ProductRenderCollectorComposite
     */
    private $productRenderCollectorComposite;

    /**
     * @var ProductRenderFactory
     */
    private $productRenderFactory;

    /**
     * @var Hydrator
     */
    private $hydrator;

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
     * @param TaxCalculationInterface $taxCalculation
     */
    public function __construct(
        ProductRenderCollectorComposite $productRenderCollectorComposite,
        ProductRenderFactory $productRenderFactory,
        CatalogHelper $catalogHelper,
        TaxCalculationInterface $taxCalculation,
        Hydrator $hydrator
    ) {
        $this->productRenderCollectorComposite = $productRenderCollectorComposite;
        $this->productRenderFactory = $productRenderFactory;
        $this->taxCalculation = $taxCalculation;
        $this->catalogHelper = $catalogHelper;
        $this->hydrator = $hydrator;
    }

    public function getPrice($product, ?int $storeId): float
    {
        if (!isset($this->prices)) {
            $this->setPrices($product, $storeId);
        }
        return $this->prices['price'] ?? 0.00;
    }

    public function getSpecialPrice($product, ?int $storeId): float
    {
        if (!isset($this->prices)) {
            $this->setPrices($product, $storeId);
        }
        return $this->prices['specialPrice'] ?? 0.00;
    }

    public function getPriceInclTax($product, ?int $storeId): float
    {
        if (!isset($this->pricesInclTax)) {
            $this->setPricesInclTax($product, $storeId);
        }
        return $this->pricesInclTax['price'] ?? 0.00;
    }

    public function getSpecialPriceInclTax($product, ?int $storeId): float
    {
        if (!isset($this->pricesInclTax)) {
            $this->setPricesInclTax($product, $storeId);
        }
        return $this->pricesInclTax['specialPrice'] ?? 0.00;
    }

    /**
     * Set prices for all product types.
     *
     * @param mixed $product
     * @param int|null $storeId
     *
     * @return void
     */
    private function setPrices($product, ?int $storeId)
    {
        if ($product->getTypeId() == 'configurable') {
            foreach ($product->getTypeInstance()->getUsedProducts($product) as $childProduct) {
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
            $price = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
            $specialPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
            //if special price equals to price then its wrong.
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
     * In catalog sync, the rate is based on the (scoped) tax origin, as configured in
     * Default Tax Destination Calculation > Default Country.
     * Here we calculate the 'inc' figures with the rate and the prices we already obtained.
     *
     * @param MagentoProduct $product
     * @param string|int|null $storeId
     * @return void
     */
    private function setPricesInclTax($product, $storeId)
    {
        $rate = $this->taxCalculation->getCalculatedRate(
            $product->getTaxClassId(),
            null,
            $storeId
        );
        $price = $this->getPrice($product, $storeId);
        $specialPrice = $this->getSpecialPrice($product, $storeId);
        $taxPrice = $this->catalogHelper->getTaxPrice($product, $price);
        $weirdPrice = $this->catalogHelper->getTaxPrice(
            $product,
            $product->getFinalPrice(),
            true,
            null,
            null,
            null,
            null,
            true
        );
        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
        $finalPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();

        $productRender = $this->productRenderFactory->create();

        $productRender->setStoreId($storeId);
        //$productRender->setCurrencyCode($store->getCurrentCurrencyCode());
        $productRender->setCurrencyCode('GBP');
        $this->productRenderCollectorComposite
            ->collect($product, $productRender);
        $data = $this->hydrator->extract($productRender);

        $priceBeforeTax = $data['price_info']['extension_attributes']['tax_adjustments']['regular_price'];
        $this->pricesInclTax['price'] = $this->formatPriceValue(
            $price + ($price * ($rate / 100))
        );
        $this->pricesInclTax['specialPrice'] = $this->formatPriceValue(
            $specialPrice + ($specialPrice * ($rate / 100))
        );
    }

    /**
     * Formats a price value.
     *
     * @param float|null $price
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
