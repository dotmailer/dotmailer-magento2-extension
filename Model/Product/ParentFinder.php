<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ParentFinder
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    private $configurableType;

    /**
     * @var \Magento\GroupedProduct\Model\Product\Type\Grouped
     */
    private $groupedType;

    /**
     * @var \Magento\Bundle\Model\ResourceModel\Selection
     */
    private $bundleSelection;

    /**
     * ParentFinder constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param Logger $logger
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType
     * @param \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        Logger $logger,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType,
        \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType,
        \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->configurableType = $configurableType;
        $this->groupedType = $groupedType;
        $this->bundleSelection = $bundleSelection;
    }

    /**
     * @param $product
     * @param string $type
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getParentProduct($product, $type = 'first_parent_id')
    {
        switch ($type) {
            case 'grouped':
                $parentId = $this->getFirstGroupedParentId($product);
                break;
            default:
                $parentId = $this->getFirstParentId($product);
        }

        if ($parentId) {
            try {
                return $this->productRepository->getById($parentId, false, $product->getStoreId());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->debug(
                    $e->getMessage() .
                    ' Parent Product: ' . $parentId .
                    ', Child Product: ' . $product->getId()
                );
            }
        }

        return null;
    }

    /**
     * Like getParentProduct(), but restricted to configurable parents only.
     *
     * @param $product
     * @param string $type
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    private function getConfigurableParentProduct($product)
    {
        $parentId = $this->getFirstConfigurableParentId($product);

        if (!$parentId) {
            return null;
        }

        try {
            return $this->productRepository->getById($parentId, false, $product->getStoreId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->debug(
                $e->getMessage() .
                ' Parent Product: ' . $parentId .
                ', Child Product: ' . $product->getId()
            );
        }

        return null;
    }

    /**
     * @param Product $product
     * @param string $imageRole
     * @return \Magento\Catalog\Api\Data\ProductInterface|Product|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getParentProductForNoImageSelection(Product $product, $imageRole = 'small_image')
    {
        $imageRole = $imageRole ?? 'small_image';
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
            && (empty($product->getData($imageRole)) || $product->getData($imageRole) == 'no_selection')
            && $parentProduct = $this->getParentProduct($product)
        ) {
            return $parentProduct;
        }

        return $product;
    }

    /**
     * @param $product
     * @return int|null
     */
    public function getProductParentIdToCatalogSync($product)
    {
        $parent = $this->getParentProduct($product);

        if ($parent && $parent->getTypeId() === Configurable::TYPE_CODE) {
            return $parent->getId();
        }

        return null;
    }

    /**
     * @param array $productIds
     * @return array
     */
    public function getConfigurableParentsFromProductIds($productIds)
    {
        $configurableParents = [];

        foreach ($productIds as $productId) {
            try {
                $product = $this->productRepository->getById($productId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->debug(
                    $e->getMessage() . ' Product ID: ' . $productId
                );
                continue;
            }

            if ($product instanceof Product) {
                $parentProduct = $this->getConfigurableParentProduct($product);
                if (isset($parentProduct)) {
                    $configurableParents[] = $parentProduct->getData();
                }
            }
        }

        return array_unique($configurableParents, SORT_REGULAR);
    }

    /**
     * @param $product
     * @return string|null
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
     * @param $product
     * @return string|null
     */
    private function getFirstConfigurableParentId($product)
    {
        $configurableProducts = $this->configurableType->getParentIdsByChild($product->getId());
        if (isset($configurableProducts[0])) {
            return $configurableProducts[0];
        }

        return null;
    }

    /**
     * @param $product
     * @return string|null
     */
    private function getFirstGroupedParentId($product)
    {
        $groupedProducts = $this->groupedType->getParentIdsByChild($product->getId());
        if (isset($groupedProducts[0])) {
            return $groupedProducts[0];
        }

        return null;
    }
}
