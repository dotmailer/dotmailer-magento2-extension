<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

use Magento\Catalog\Model\ResourceModel\Product;

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
     * @var \Dotdigitalgroup\Email\Model\Product\ParentFinder
     */
    private $parentFinder;

    /**
     * @var Product
     */
    private $productResource;

    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Dotdigitalgroup\Email\Model\Product\ParentFinder $parentFinder,
        Product $productResource
    ) {
        $this->catalogResource = $catalogResource;
        $this->catalogFactory = $catalogFactory;
        $this->dateTime = $dateTime;
        $this->parentFinder = $parentFinder;
        $this->productResource = $productResource;
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
     * Adds products to email_catalog or
     * marks existing products (and their parents) as unprocessed.
     *
     * @param array $bunch
     */
    private function processBatch($bunch)
    {
        $bunchProductIds = $this->productResource->getProductsIdsBySkus(
            array_unique(array_column($bunch, 'sku'))
        );
        $existingProductIds = $this->getExistingProductIds($bunchProductIds);
        $newEntryIds = array_diff($bunchProductIds, $existingProductIds);

        if (!empty($newEntryIds)) {
            $createdAt = $this->dateTime->formatDate(true);

            $newEntries = array_map(function ($id) use ($createdAt) {
                return [
                    'product_id' => $id,
                    'processed' => 0,
                    'created_at' => $createdAt
                ];
            }, $newEntryIds);

            $this->catalogResource->bulkProductImport($newEntries);
        }

        if (!empty($existingProductIds)) {
            $parentProductIds = $this->parentFinder->getConfigurableParentsFromProductIds($existingProductIds);
            $this->catalogResource->setUnprocessedByIds(
                array_merge($existingProductIds, $parentProductIds)
            );
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
