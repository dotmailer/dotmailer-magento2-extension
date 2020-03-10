<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ParentFinder
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Data
     */
    private $helper;

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
     * @param Data $helper
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType
     * @param \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        Data $helper,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableType,
        \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedType,
        \Magento\Bundle\Model\ResourceModel\Selection $bundleSelection
    ) {
        $this->productRepository = $productRepository;
        $this->helper = $helper;
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
        try {
            if ($parentId = $this->getFirstParentId($product)) {
                return $this->productRepository->getById($parentId, false, $product->getStoreId());
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->helper->debug(
                $e->getMessage() . ' Parent Product: ' .
                $parentId . ',
                Child Product: ' . $product->getId()
            );
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfigurableParentsFromBunchOfProducts($products)
    {
        $configurableParents = [];

        foreach ($products as $product) {
            if (is_array($product) && isset($product['sku'])) {
                $product = $this->productRepository->get($product['sku']);
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
