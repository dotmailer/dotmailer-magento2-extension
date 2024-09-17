<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Record\RecordImportedStrategyInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk;

class OrderImportedStrategy implements RecordImportedStrategyInterface
{
    /**
     * @var array
     */
    private $records = [];

    /**
     * @var OrderResourceFactory
     */
    private $orderResourceFactory;

    /**
     * @var UpdateCatalogBulk
     */
    private $bulkUpdate;

    /**
     * OrderImportedStrategy constructor.
     *
     * @param OrderResourceFactory $orderResourceFactory Factory for creating order resource instances.
     * @param UpdateCatalogBulk $bulkUpdate Service for bulk updating the catalog.
     */
    public function __construct(
        OrderResourceFactory $orderResourceFactory,
        UpdateCatalogBulk $bulkUpdate
    ) {
        $this->orderResourceFactory = $orderResourceFactory;
        $this->bulkUpdate = $bulkUpdate;
    }

    /**
     * Sets the data to be processed by the strategy.
     *
     * @param array $records The data to be processed by the strategy.
     * @return OrderImportedStrategy Returns the instance of the strategy for method chaining.
     */
    public function setRecords(array $records): OrderImportedStrategy
    {
        $this->records = $records;
        return $this;
    }

    /**
     * Process the set records.
     *
     * @return void
     */
    public function process(): void
    {
        $this->orderResourceFactory->create()->setImportedDateByIds(array_keys($this->records));
        $this->bulkUpdate->execute($this->getAllProductsFromBatch($this->records));
    }

    /**
     * Get all products from the batch of records.
     *
     * @param array $insightRecords
     * @return array Returns an array of products extracted from the records.
     */
    private function getAllProductsFromBatch(array $insightRecords): array
    {
        $allProducts = [];
        foreach ($insightRecords as $insightRecord) {
            $recordData = $insightRecord->getJson();
            if (!isset($recordData['products'])) {
                continue;
            }
            foreach ($recordData['products'] as $product) {
                $allProducts[] = $product;
            }
        }

        return $allProducts;
    }
}
