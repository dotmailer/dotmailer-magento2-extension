<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\Product\CurrentProductInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;

class VariantDataEncoder
{
    /**
     * @var CurrentProductInterface
     */
    private $currentProduct;

    /**
     * @var ConfigurableResource
     */
    private $configurableResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param CurrentProductInterface $currentProduct
     * @param ConfigurableResource $configurableResource
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        CurrentProductInterface $currentProduct,
        ConfigurableResource $configurableResource,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository
    ) {
        $this->currentProduct = $currentProduct;
        $this->configurableResource = $configurableResource;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
    }

    /**
     * Set out of stock variants (all products visible).
     *
     * If Stock Options > Display Out of Stock Products = Yes,
     * check the stock of all 'allowed' children and return the out of stock ones.
     *
     * @return false|string
     */
    public function setOutOfStockVariantsForVisibleOutOfStock()
    {
        $variants = [];
        $product = $this->currentProduct->getProduct();
        /** @var Product $product */
        if ($product->getTypeId() !== 'configurable') {
            return json_encode($variants);
        }
        $configurableProductInstance = $product->getTypeInstance();
        /** @var Configurable $configurableProductInstance */
        $childProducts = $configurableProductInstance->getUsedProducts($product);
        foreach ($childProducts as $product) {
            /** @var Product $product */
            if (!$product->isSalable()) {
                $variants[] = [
                    'id' => $product->getId(),
                    'title' => $this->getVariantName($product),
                    'available' => false
                ];
            }
        }

        return json_encode($variants);
    }

    /**
     * Set out of stock variants (out of stock products not visible).
     *
     * If Stock Options > Display Out of Stock Products = No, compare ALL children
     * with DISPLAYED children to return the out of stock ones.
     *
     * @return false|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setOutOfStockVariantsForHiddenOutOfStock()
    {
        $variants = [];
        $product = $this->currentProduct->getProduct();
        /** @var Product $product */
        if ($product->getTypeId() !== 'configurable') {
            return json_encode($variants);
        }
        $allVariantIds = $this->configurableResource->getChildrenIds($product->getId());
        $configurableProductInstance = $product->getTypeInstance();
        /** @var Configurable $configurableProductInstance */
        $visibleVariants = $configurableProductInstance->getUsedProducts($product);
        $visibleVariantIds = [];

        foreach ($visibleVariants as $visibleVariant) {
            $visibleVariantIds[] = $visibleVariant->getId();
        }

        $websiteId = $this->storeManager->getWebsite()->getId();
        foreach (array_diff($allVariantIds[0], $visibleVariantIds) as $variantId) {
            $product = $this->productRepository->getById($variantId);

            /** @var Product $product */
            if (in_array($websiteId, $product->getWebsiteIds())) {
                $variants[] = [
                    'id' => $product->getId(),
                    'title' => $this->getVariantName($product),
                    'available' => false
                ];
            }
        }

        return json_encode($variants);
    }

    /**
     * Returns the name of a variant as combination of configurations.
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getVariantName($product)
    {
        $name = [];
        $options = [];
        $parentProduct = $this->currentProduct->getProduct();
        /** @var Product $parentProduct */
        $configurableProductInstance = $parentProduct->getTypeInstance();
        /** @var Configurable $configurableProductInstance */
        $productAttributeOptions = $configurableProductInstance->getConfigurableAttributesAsArray($parentProduct);
        foreach ($productAttributeOptions as $attributeOption) {
            $options[] = $attributeOption;
        }

        foreach ($options as $option) {
            $attributeCode = $option['attribute_code'] ?? null;
            foreach ($option['values'] as $value) {
                if ($attributeCode && $product[$attributeCode] === $value['value_index']) {
                    $name[] = $value['store_label'] ?? null;
                }
            }
        }

        return implode("-", $name);
    }
}
