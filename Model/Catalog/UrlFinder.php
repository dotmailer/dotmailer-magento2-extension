<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

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
     * UrlFinder constructor.
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType
     */
    public function __construct(
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection,
        \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType
    ) {
        $this->configurableType = $configurableType;
        $this->productRepository = $productRepository;
        $this->bundleSelection = $bundleSelection;
        $this->groupedType = $groupedType;
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
        if ($product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE &&
            $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
        ) {
            $parentId = $this->getFirstParentId($product);
            if (isset($parentId)) {
                /** @var \Magento\Catalog\Model\Product $parentProduct */
                $parentProduct = $this->productRepository->getById($parentId, false, $product->getStoreId());
                return $parentProduct->getProductUrl();
            }
        }
        return $product->getProductUrl();
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
}
