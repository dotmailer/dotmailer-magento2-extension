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
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getParentProduct($product)
    {
        $parentId = $this->getFirstParentId($product);

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
     * @param array $products
     * @return array
     */
    public function getConfigurableParentsFromBunchOfProducts($products)
    {
        $configurableParents = [];

        foreach ($products as $product) {
            if (is_array($product) && isset($product['sku'])) {
                try {
                    $product = $this->productRepository->get($product['sku']);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $this->logger->debug(
                        $e->getMessage() .
                        ' SKU not found: ' . $product['sku']
                    );
                    continue;
                }
            }
            if ($product instanceof Product) {
                $parentProduct = $this->getParentProduct($product);
                if (isset($parentProduct) && $parentProduct->getTypeId() === 'configurable') {
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
}
