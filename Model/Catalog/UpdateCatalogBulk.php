<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

class UpdateCatalogBulk
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalogResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    private $catalogFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    private $searchCriteria;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    private $filterGroup;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Api\SearchCriteriaInterface $criteria,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\FilterBuilder $filterBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->catalogResource = $catalogResource;
        $this->catalogFactory = $catalogFactory;
        $this->dateTime = $dateTime;
        $this->searchCriteria = $criteria;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param $chunkBunches
     */
    public function execute($bunch)
    {
        $bunchLimit = 500;
        $chunkBunches = array_chunk($bunch, $bunchLimit);

        foreach ($chunkBunches as $chunk) {
            $this->processBatch($chunk);
        }
    }

    /**
     * Process creates or updates a catalog with products
     * @param $bunch
     */
    private function processBatch($bunch)
    {
        $productIds = $this->getProductIdsInBunch($bunch);
        $existingProductIds = $this->getExistingProductIds($productIds);

        $newEntryIds = array_diff($productIds, $existingProductIds);
        $createdAt = $this->dateTime->formatDate(true);

        $newEntries = array_map(function ($id) use ($createdAt) {
            return [
                'product_id' => $id,
                'imported' => null,
                'modified' => null,
                'created_at' => $createdAt
            ];
        }, $newEntryIds);

        if (!empty($newEntries)) {
            $this->catalogResource->bulkProductImport($newEntries);
        }

        if (!empty($existingProductIds)) {
            $this->catalogResource->setModified($existingProductIds);
        }
    }

    /**
     * Returns all product Id's that belongs to Catalog Collection
     * @return array
     */
    private function getExistingProductIds($productIds)
    {
        $connectorCollection = $this->catalogFactory->create()
            ->addFieldToFilter('product_id', ['in' => $productIds])
            ->addFieldToSelect(['product_id']);

        $catalogIds = $connectorCollection->getColumnValues('product_id');
        return $catalogIds;
    }

    /**
     * @param $bunch
     * @return array
     */
    private function getProductIdsInBunch($bunch)
    {
        $bunchSkus = array_map(function ($importedEntry) {
            return $importedEntry['sku'];
        }, $bunch);

        $this->filterGroup->setFilters([
            $this->filterBuilder
                ->setField('sku')
                ->setConditionType('in')
                ->setValue($bunchSkus)
                ->create()
        ]);

        $this->searchCriteria->setFilterGroups([$this->filterGroup]);
        $products = $this->productRepository->getList($this->searchCriteria);
        $productItems = $products->getItems();

        $productIds = array_map(function ($product) {
            return $product->getId();
        }, $productItems);

        return $productIds;
    }
}
