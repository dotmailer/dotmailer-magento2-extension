<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Connector\ContactData;

use Dotdigitalgroup\Email\Model\Sync\Export\BrandAttributeFinder;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogCollectionFactory;

class ProductLoader
{
    /**
     * @var BrandAttributeFinder
     */
    private $brandAttributeFinder;

    /**
     * @var CatalogCollectionFactory
     */
    private $catalogCollectionFactory;

    /**
     * @var array
     */
    private $products = [];

    /**
     * @param BrandAttributeFinder $brandAttributeFinder
     * @param CatalogCollectionFactory $catalogCollectionFactory
     */
    public function __construct(
        BrandAttributeFinder $brandAttributeFinder,
        CatalogCollectionFactory $catalogCollectionFactory
    ) {
        $this->brandAttributeFinder = $brandAttributeFinder;
        $this->catalogCollectionFactory = $catalogCollectionFactory;
    }

    /**
     * Get cached product by id.
     *
     * @param int $productId
     * @param int $storeId
     *
     * @return Product|null
     */
    public function getCachedProductById(int $productId, int $storeId)
    {
        if (!isset($this->products[$productId][$storeId])) {
            $this->setProducts([$productId], $storeId);
        }

        return $this->products[$productId][$storeId] ?? null;
    }

    /**
     * Get cached products.
     *
     * @param array $productIds
     * @param int $storeId
     *
     * @return Product[]
     */
    public function getCachedProducts(array $productIds, int $storeId)
    {
        $productIdsNotAlreadyCached = array_diff($productIds, array_keys($this->products));
        if (! empty($productIdsNotAlreadyCached)) {
            $this->setProducts($productIdsNotAlreadyCached, $storeId);
        }

        $productIdsNotAlreadyCachedForThisStore = array_filter($productIds, function ($productId) use ($storeId) {
            return !isset($this->products[$productId][$storeId]);
        });
        if (! empty($productIdsNotAlreadyCachedForThisStore)) {
            $this->setProducts($productIdsNotAlreadyCachedForThisStore, $storeId);
        }

        $productsToReturn = [];
        foreach ($productIds as $productId) {
            if (!isset($this->products[$productId][$storeId])) {
                continue;
            }
            $productsToReturn[] = $this->products[$productId][$storeId];
        }

        return $productsToReturn;
    }

    /**
     * Set products.
     *
     * Load a product by id and store it to prevent repeat loading of the same entity.
     * If we don't find a match for a product id, set it to null to prevent repeat attempts
     * to load the missing product.
     *
     * @param array $productIds
     * @param int $storeId
     *
     * @return void
     */
    private function setProducts(array $productIds, int $storeId)
    {
        $productsCollection = $this->catalogCollectionFactory->create()
            ->addStoreFilter($storeId)
            ->addIdFilter($productIds);

        if ($brandAttributeCode = $this->brandAttributeFinder->getBrandAttributeCodeByStoreId($storeId)) {
            $productsCollection->addAttributeToSelect($brandAttributeCode);
        }

        $productsCollection->load();

        foreach ($productIds as $productId) {
            $this->products[$productId][$storeId] = $productsCollection->getItemById($productId) ?? null;
        }
    }
}
