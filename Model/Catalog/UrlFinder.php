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
     * UrlFinder constructor.
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->configurableType = $configurableType;
        $this->productRepository = $productRepository;
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
            $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
            if (isset($parentIds[0])) {
                /** @var \Magento\Catalog\Model\Product $parentProduct */
                $parentProduct = $this->productRepository->getById($parentIds[0], false, $product->getStoreId());
                return $parentProduct->getProductUrl();
            }
        }
        return $product->getProductUrl();
    }
}
