<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider\Attributes;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductPriceProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Dotdigitalgroup\Email\Model\Tax\TaxCalculator;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;

class ProductPriceProvider implements ProductPriceProviderInterface
{
    /**
     * @var ProductProviderInterface
     */
    private $productProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TaxCalculator
     */
    private $taxCalculator;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param ProductProviderInterface $productProvider
     * @param StoreManagerInterface $storeManager
     * @param TaxCalculator $taxCalculator
     * @param CustomerSession $customerSession
     */
    public function __construct(
        ProductProviderInterface $productProvider,
        StoreManagerInterface $storeManager,
        TaxCalculator $taxCalculator,
        CustomerSession $customerSession
    ) {
        $this->productProvider = $productProvider;
        $this->storeManager = $storeManager;
        $this->taxCalculator = $taxCalculator;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritDoc
     */
    public function getPrice(): float
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        if (!$product) {
            return 0.0;
        }

        $priceInfo = $product->getPriceInfo();
        $price = $priceInfo->getPrice('regular_price')->getAmount()->getBaseAmount();

        return (float) $price;
    }

    /**
     * @inheritDoc
     */
    public function getPriceInclTax(): float
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        if (!$product) {
            return 0.0;
        }

        $priceInfo = $product->getPriceInfo();
        $price = $priceInfo->getPrice('regular_price')->getAmount()->getBaseAmount();

        return $this->taxCalculator->calculatePriceInclTax(
            $product,
            (float)$price,
            $this->getStoreId(),
            $this->getCustomerId()
        );
    }

    /**
     * @inheritDoc
     */
    public function getSalePrice(): float
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        if (!$product) {
            return 0.0;
        }

        $priceInfo = $product->getPriceInfo();
        $price = $priceInfo->getPrice('final_price')->getAmount()->getBaseAmount();

        return (float) $price;
    }

    /**
     * @inheritDoc
     */
    public function getSalePriceInclTax(): float
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        if (!$product) {
            return 0.0;
        }

        $priceInfo = $product->getPriceInfo();
        $price = $priceInfo->getPrice('final_price')->getAmount()->getBaseAmount();

        return $this->taxCalculator->calculatePriceInclTax(
            $product,
            (float)$price,
            $this->getStoreId(),
            $this->getCustomerId()
        );
    }

    /**
     * @inheritDoc
     */
    public function getCurrencyCode(): string
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        return $store->getBaseCurrency()->getCurrencyCode();
    }

    /**
     * Get current store ID
     *
     * @return int
     */
    private function getStoreId(): int
    {
        return (int)$this->storeManager->getStore()->getId();
    }

    /**
     * Get current customer ID if available
     *
     * @return int|null
     */
    private function getCustomerId(): ?int
    {
        return $this->customerSession->isLoggedIn() ? (int)$this->customerSession->getCustomerId() : null;
    }
}
