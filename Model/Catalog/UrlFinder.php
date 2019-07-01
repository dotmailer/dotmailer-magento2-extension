<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Model\Product;

class UrlFinder
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    private $configurableType;

    /**
     * @var \Magento\Bundle\Model\ResourceModel\Selection
     */
    private $bundleSelection;

    /**
     * @var \Magento\GroupedProduct\Model\Product\Type\Grouped;
     */
    private $groupedType;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ImageBuilder
     */
    private $imageBuilder;

    /**
     * UrlFinder constructor.
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Block\Product\ImageBuilderFactory $imageBuilderFactory
     */
    public function __construct(
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection,
        \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Block\Product\ImageBuilderFactory $imageBuilderFactory
    ) {
        $this->configurableType = $configurableType;
        $this->productRepository = $productRepository;
        $this->bundleSelection = $bundleSelection;
        $this->groupedType = $groupedType;
        $this->storeManager = $storeManager;
        $this->imageBuilder = $imageBuilderFactory->create();
    }

    /**
     * Fetch a URL for a product depending on its visibility and type.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function fetchFor($product)
    {
        $product = $this->getScopedProduct($product);

        if (
            $product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
            && $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            && $parentProduct = $this->getParentProduct($product)
        ) {
            return $parentProduct->getProductUrl();
        }

        return $product->getProductUrl();
    }

    /**
     * Get a product image URL, or that of it's parent if no image is set
     *
     * @param Product $product
     * @param string $imageId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductImageUrl(Product $product, string $imageId)
    {
        $product = $this->getScopedProduct($product);

        if (
            $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            && $product->getSmallImage() == 'no_selection'
            && $parentProduct = $this->getParentProduct($product)
        ) {
            $product = $parentProduct;
        }

        $imageData = $this->imageBuilder
            ->setProduct($product)
            ->setImageId($imageId)
            ->create()
            ->getData();

        return $imageData['image_url'] ?? null;
    }

    /**
     * Return Parent Id for configurable, grouped or bundled products (in that order of priority)
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return mixed
     */
    private function getFirstParentId($product)
    {
        $configurableProducts = $this->configurableType->getParentIdsByChild($product->getId());
        if (isset($configurableProducts[0])) {
            return $configurableProducts[0];
        }

        $groupedProducts = $this->groupedType->getParentIdsByChild($product->getId());
        if (isset($groupedProducts[0])) {
            return $groupedProducts[0];
        }

        $bundleProducts = $this->bundleSelection->getParentIdsByChild($product->getId());
        if (isset($bundleProducts[0])) {
            return $bundleProducts[0];
        }

        return null;
    }

    /**
     * In default-level catalog sync, the supplied Product's store ID can be 1 even though the product is not in store 1
     * This method finds the default store of the first website the product belongs to, and uses that to get a new product.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getScopedProduct($product)
    {
        if (!in_array($product->getStoreId(), $product->getStoreIds())) {

            $productInWebsites = $product->getWebsiteIds();
            $firstWebsite = $this->storeManager->getWebsite($productInWebsites[0]);
            $storeId = (int) $firstWebsite->getDefaultGroup()->getDefaultStoreId();

            return $this->productRepository->getById($product->getId(), false, $storeId);
        }

        return $product;
    }

    /**
     * @param $product
     * @return \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getParentProduct($product)
    {
        if ($parentId = $this->getFirstParentId($product)) {
            return $this->productRepository->getById($parentId, false, $product->getStoreId());
        }
        return null;
    }
}
