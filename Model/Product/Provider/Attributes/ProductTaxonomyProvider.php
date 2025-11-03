<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider\Attributes;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductTaxonomyProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class ProductTaxonomyProvider implements ProductTaxonomyProviderInterface
{
    /**
     * @var ProductProviderInterface
     */
    private $productProvider;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ProductProviderInterface $productProvider
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductProviderInterface $productProvider,
        Data $helper,
        StoreManagerInterface $storeManager
    ) {
        $this->productProvider = $productProvider;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function getBrand(): ?string
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        try {
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            $attribute = $this->helper->getBrandAttributeByWebsiteId($websiteId);
            $brandAttribute = $product->getCustomAttribute($attribute);

            if (!$brandAttribute instanceof AttributeInterface) {
                return null;
            }

            $textAttribute = $product->getAttributeText(
                $brandAttribute->getAttributeCode()
            );

            return $textAttribute ? (string)$textAttribute : null;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCategories(): Collection
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        return $product->getCategoryCollection();
    }
}
