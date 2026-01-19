<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider\Attributes;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductTaxonomyProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
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
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ProductProviderInterface $productProvider
     * @param Data $helper
     * @param CollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductProviderInterface $productProvider,
        Data $helper,
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->productProvider = $productProvider;
        $this->helper = $helper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
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
     * Get the product category collection.
     *
     * If product has no category ids, return the cached (empty) category collection.
     * Otherwise, always hydrate a fresh collection in case collection was loaded elsewhere.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getCategories(): Collection
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        if (!$categoryIds = $product->getCategoryIds()) {
            return $product->getCategoryCollection();
        }

        try {
            return $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('name')
                ->addIdFilter($categoryIds);
        } catch (LocalizedException $e) {
            return $product->getCategoryCollection();
        }
    }
}
