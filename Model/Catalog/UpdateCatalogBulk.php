<?php

namespace Dotdigitalgroup\Email\Model\Catalog;

use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Stdlib\DateTime;

class UpdateCatalogBulk
{
    /**
     * @var Catalog
     */
    private $catalogResource;

    /**
     * @var CollectionFactory
     */
    private $catalogFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ParentFinder
     */
    private $parentFinder;

    /**
     * @var Product
     */
    private $productResource;

    /**
     * UpdateCatalogBulk constructor.
     *
     * @param Catalog $catalogResource
     * @param CollectionFactory $catalogFactory
     * @param DateTime $dateTime
     * @param ParentFinder $parentFinder
     * @param Product $productResource
     */
    public function __construct(
        Catalog $catalogResource,
        CollectionFactory $catalogFactory,
        DateTime $dateTime,
        ParentFinder $parentFinder,
        Product $productResource
    ) {
        $this->catalogResource = $catalogResource;
        $this->catalogFactory = $catalogFactory;
        $this->dateTime = $dateTime;
        $this->parentFinder = $parentFinder;
        $this->productResource = $productResource;
    }

    /**
     * Process bunch of products.
     *
     * @param array $bunch
     */
    public function execute($bunch)
    {
        $bunchLimit = 500;
        $chunkBunches = array_chunk($bunch, $bunchLimit);

        foreach ($chunkBunches as $chunk) {
            $productIds = $this->getProductIdsFromSku($chunk);
            $this->processBatch($productIds);
        }
    }

    /**
     * Process bunch of product ids.
     *
     * @param array $bunch
     */
    public function executeByIds($bunch)
    {
        $bunchLimit = 500;
        $chunkBunches = array_chunk($bunch, $bunchLimit);

        foreach ($chunkBunches as $chunk) {
            $this->processBatch($chunk);
        }
    }

    /**
     * Returns product ids from product collection.
     *
     * @param array $bunch
     * @return array
     */
    private function getProductIdsFromSku($bunch)
    {
        return $this->productResource->getProductsIdsBySkus(
            array_unique(array_column($bunch, 'sku'))
        );
    }

    /**
     * Process bunch of product ids.
     *
     * Adds products to email_catalog or marks existing products (and their parents) as unprocessed.
     *
     * @param array $bunchProductIds
     */
    private function processBatch($bunchProductIds)
    {
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
            $parentProductIds = $this->parentFinder->getParentIdsFromProductIds($existingProductIds);
            $this->catalogResource->setUnprocessedByIds(
                array_merge($existingProductIds, $parentProductIds)
            );
        }
    }

    /**
     * Returns all product Id's that belongs to Catalog Collection
     *
     * @param array $productIds
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
