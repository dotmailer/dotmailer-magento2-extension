<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Export;

use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

class CategoryNameFinder
{
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get category names by store.
     *
     * @param WebsiteInterface $website
     * @param array $mappedFields
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCategoryNamesByStore(WebsiteInterface $website, array $mappedFields): array
    {
        if (! $this->categoryDataFieldsAreMapped($mappedFields)) {
            return [];
        }

        $categoryNames = [];
        /** @var \Magento\Store\Model\Website $website */
        $website = $this->storeManager->getWebsite($website->getId());

        foreach ($website->getStores() as $store) {
            $categoryCollection = $this->categoryCollectionFactory->create()
                ->addNameToResult()
                ->setStore($store->getId())
                ->setPageSize(10000);

            foreach ($categoryCollection as $category) {
                $categoryNames[$store->getId()][$category->getId()] = $category->getName();
            }
        }

        return $categoryNames;
    }

    /**
     * Check if category data fields are mapped.
     *
     * @param array $mappedFields
     *
     * @return bool
     */
    private function categoryDataFieldsAreMapped(array $mappedFields): bool
    {
        $fields = [
            Datafield::FIRST_CATEGORY_PUR,
            Datafield::LAST_CATEGORY_PUR,
            Datafield::MOST_PUR_CATEGORY
        ];
        $mapped = array_intersect($fields, array_keys($mappedFields));
        return count($mapped) > 0;
    }
}
