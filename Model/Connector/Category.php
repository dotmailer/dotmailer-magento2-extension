<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Catalog\Model\ResourceModel\Category\Collection;

class Category
{
    /**
     * @var array
     */
    private $categoryNames = [];

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var Collection
     */
    protected Collection $categoryCollection;

    /**
     * @var CategoryResource
     */
    protected CategoryResource $categoryResource;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Collection $categoryCollection
     * @param CategoryResource $categoryResource
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Collection $categoryCollection,
        CategoryResource $categoryResource
    ) {
        $this->storeManager = $storeManager;
        $this->categoryCollection = $categoryCollection;
        $this->categoryResource = $categoryResource;
        $this->init();
    }

    /**
     * @return Category
     */
    public function init()
    {
        foreach ($this->categoryCollection->setPageSize(10000)->load() as $category) {
            /** @var Category $category */
            foreach ($this->storeManager->getStores() as $store) {
                $category->setStoreId($store->getId());
                $this->categoryResource->load($category, $category->getId());
                if (!array_key_exists($category->getId(), $this->categoryNames)) {
                    $this->categoryNames[$category->getId()] = [];
                }
                if (!array_key_exists($store->getId(), $this->categoryNames[$category->getId()])) {
                    $this->categoryNames[$category->getId()][$store->getId()] = null;
                }
                $this->categoryNames[$category->getId()][$store->getId()] = $category->getName();
            }
        }

        return $this;
    }

    /**
     *
     * Get category names.
     *
     * @param array $categoryIds
     * @return string
     */
    public function getCategoryNames($categoryIds, $storeId): string
    {
        $names = [];
        foreach ($categoryIds as $id) {
            if (array_key_exists($id, $this->categoryNames)
                && array_key_exists($storeId, $this->categoryNames[$id])) {
                $names[$id] = $this->categoryNames[$id][$storeId];
            }
        }

        //comma separated category names
        if (count($names)) {
            return implode(',', $names);
        }

        return '';
    }
}
