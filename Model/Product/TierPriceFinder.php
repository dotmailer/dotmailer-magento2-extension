<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\TierPriceFinderInterface;
use Dotdigitalgroup\Email\Model\Connector\ContactData\CustomerGroupLoader;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;

class TierPriceFinder implements TierPriceFinderInterface
{
    /**
     * @var CustomerGroupLoader
     */
    private $customerGroupLoader;

    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var int|null
     */
    private $storeId;

    /**
     * @var int
     */
    private $customerGroupId;

    /**
     * TierPriceFinder constructor.
     *
     * @param CustomerGroupLoader $customerGroupLoader
     * @param TaxCalculator $taxCalculator
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CustomerGroupLoader $customerGroupLoader,
        TaxCalculator $taxCalculator,
        ProductRepository $productRepository
    ) {
        $this->customerGroupLoader = $customerGroupLoader;
        $this->taxCalculator = $taxCalculator;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritDoc
     */
    public function getTierPrices($product)
    {
        return [];
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     */
    public function getTierPricesByStoreAndGroup(
        ProductInterface $product,
        ?int $storeId,
        ?int $customerGroupId = null
    ): array {
        $this->storeId = $storeId;
        $this->customerGroupId = $customerGroupId;

        switch ($product->getTypeId()) {
            case 'bundle':
                return $this->getTierPricesOfBundledProduct($product);
            case 'configurable':
                return $this->getMinTierPriceOfChildProducts($product);
            case 'grouped':
                return [];
            default:
                return $this->getTierPricesOfSimpleProduct($product);
        }
    }

    /**
     * Get tier prices of simple product.
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    private function getTierPricesOfSimpleProduct(ProductInterface $product)
    {
        $prices = [];
        foreach ($this->getAvailableTierPrices($product) as $tier) {
            $prices[] = $this->buildTierPrice($tier, $product);
        }
        return $prices;
    }

    /**
     * Get minimum tier price of configurable child products.
     *
     * We loop through all child products, and all their tiers, to find the ultimate 'as low as' price.
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    private function getMinTierPriceOfChildProducts(ProductInterface $product): array
    {
        /** @var Product $product */
        $configurableProductInstance = $product->getTypeInstance();
        /** @var Configurable $configurableProductInstance */
        $childProducts = $configurableProductInstance->getUsedProducts($product);
        $availableTiersByGroup = $cheapestTiersByGroup = [];

        /** @var Product $childProduct */
        foreach ($childProducts as $childProduct) {
            if ($this->storeId && !in_array($this->storeId, $childProduct->getStoreIds())) {
                continue;
            }
            $availableTiers = $this->getTierPricesOfSimpleProduct($childProduct);
            if (empty($availableTiers)) {
                continue;
            }
            foreach ($availableTiers as $availableTier) {
                $availableTiersByGroup[$availableTier['customer_group']][] = $availableTier;
            }
        }

        foreach ($availableTiersByGroup as $groupTiers) {
            usort($groupTiers, function ($a, $b) {
                return $a['price'] <=> $b['price'];
            });
            $cheapestTiersByGroup[] = $groupTiers[0];
        }

        return $cheapestTiersByGroup;
    }

    /**
     * Get tier prices of bundled product.
     *
     * If bundle parent has tier price, then it will be applied to all child products.
     *
     * @param ProductInterface $product
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function getTierPricesOfBundledProduct(ProductInterface $product)
    {
        /** @var Product $product */
        $typeInstance = $product->getTypeInstance();
        $childGroups = $typeInstance->getChildrenIds($product->getId(), true);
        $prices = [];
        foreach ($this->getAvailableTierPrices($product) as $tier) {
            $parentTierPrice = $this->buildTierPrice($tier, $product);
            $parentTierPricePercentageDiscount = $parentTierPrice['percentage'] ?? 0.0;
            $tempArr = [];
            $childPrices = [];
            foreach ($childGroups as $key => $groups) {
                foreach ($groups as $simpleProductId) {
                    $childProduct = $this->productRepository->getById($simpleProductId);
                    $tempArr[] = $childProduct->getPrice() -
                        ($parentTierPricePercentageDiscount/100) * $childProduct->getPrice();
                }
                if (empty($tempArr)) {
                    continue;
                }
                $childPrices[$key] = min($tempArr);
                $tempArr = [];
            }
            if ($finalPrice = array_sum($childPrices)) {
                $parentTierPrice['price'] = $finalPrice;
                $parentTierPrice['price_incl_tax'] = $this->taxCalculator->calculatePriceInclTax(
                    $product,
                    $finalPrice,
                    $this->storeId
                );
            }
            $prices[] = $parentTierPrice;
        }

        return $prices;
    }

    /**
     * Get available tier prices.
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    private function getAvailableTierPrices(ProductInterface $product)
    {
        $availableTiers = [];
        foreach ($product->getTierPrices() as $tier) {
            if (!empty($this->customerGroupId) && $tier->getCustomerGroupId() != $this->customerGroupId) {
                continue;
            }
            $availableTiers[] = $tier;
        }
        return $availableTiers;
    }

    /**
     * Construct an item in our tier price array.
     *
     * @param ProductTierPriceInterface $tier
     * @param ProductInterface $product
     *
     * @return array
     */
    private function buildTierPrice(ProductTierPriceInterface $tier, ProductInterface $product): array
    {
        $customerGroupId = (int) $tier->getCustomerGroupId();
        $percentage = $tier->getExtensionAttributes()->getPercentageValue();
        $price = floatval($tier->getValue());
        /** @var Product $product */
        $price_incl_tax = $this->taxCalculator->calculatePriceInclTax(
            $product,
            $price,
            $this->storeId
        );

        return [
            'customer_group' => $this->customerGroupLoader->getCustomerGroup($customerGroupId),
            'price' => $price,
            'price_incl_tax' => $price_incl_tax,
            'quantity' => (int) $tier->getQty(),
            'percentage' => $percentage,
            'type' => isset($percentage) ? 'Percentage Discount' : 'Fixed Price'
        ];
    }
}
