<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Connector\ContactData;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class ProductLoader
{
    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var array
     */
    private $products = [];

    /**
     * @param ProductInterfaceFactory $productFactory
     * @param ProductResource $productResource
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductResource $productResource
    ) {
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
    }

    /**
     * Load a product by id and store it to prevent repeat loading of the same entity.
     *
     * @param int $productId
     * @param int $storeId
     *
     * @return Product
     */
    public function getProduct(int $productId, int $storeId)
    {
        if (!isset($this->products[$productId])) {
            /** @var Product $product */
            $product = $this->productFactory->create();
            $product->setStoreId($storeId);
            $this->productResource->load($product, $productId);
            $this->products[$productId] = $product;
        }
        return $this->products[$productId];
    }
}
