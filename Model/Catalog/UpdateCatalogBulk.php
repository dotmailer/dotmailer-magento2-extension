<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

class UpdateCatalogBulk
{
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
     * @var \Dotdigitalgroup\Email\Model\Product\Bunch
     */
    private $bunch;

    /**
     * @var \Dotdigitalgroup\Email\Model\Product\ParentFinder
     */
    private $parentFinder;

    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Dotdigitalgroup\Email\Model\Product\Bunch $bunch,
        \Dotdigitalgroup\Email\Model\Product\ParentFinder $parentFinder
    ) {
        $this->catalogResource = $catalogResource;
        $this->catalogFactory = $catalogFactory;
        $this->dateTime = $dateTime;
        $this->bunch = $bunch;
        $this->parentFinder = $parentFinder;
    }

    /**
     * @param $bunch
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
        $mergedWithConfigurableParents = array_merge(
            $bunch,
            $this->parentFinder->getConfigurableParentsFromBunchOfProducts($bunch)
        );

        $productIds = $this->bunch->getProductIdsBySkuInBunch($mergedWithConfigurableParents);
        $existingProductIds = $this->getExistingProductIds($productIds);

        $newEntryIds = array_diff($productIds, $existingProductIds);
        $createdAt = $this->dateTime->formatDate(true);

        $newEntries = array_map(function ($id) use ($createdAt) {
            return [
                'product_id' => $id,
                'processed' => 0,
                'created_at' => $createdAt
            ];
        }, $newEntryIds);

        if (!empty($newEntries)) {
            $this->catalogResource->bulkProductImport($newEntries);
        }

        if (!empty($existingProductIds)) {
            $this->catalogResource->setUnprocessedByIds($existingProductIds);
        }
    }

    /**
     * Returns all product Id's that belongs to Catalog Collection
     * @param $productIds
     * @return array
     */
    private function getExistingProductIds($productIds)
    {
        $connectorCollection = $this->catalogFactory->create()
            ->addFieldToFilter('product_id', ['in' => $productIds])
            ->addFieldToSelect(['product_id']);

        return $connectorCollection->getColumnValues('product_id');
    }
}
