<?php

namespace Dotdigitalgroup\Email\Model\Sync\Order;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class BatchProcessor
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UpdateCatalogBulk
     */
    private $bulkUpdate;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var OrderResourceFactory
     */
    private $orderResourceFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * Batch processor constructor.
     *
     * @param Logger $logger
     * @param UpdateCatalogBulk $bulkUpdate
     * @param ImporterFactory $importerFactory
     * @param OrderResourceFactory $orderResourceFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Logger $logger,
        UpdateCatalogBulk $bulkUpdate,
        ImporterFactory $importerFactory,
        OrderResourceFactory $orderResourceFactory,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->logger = $logger;
        $this->bulkUpdate = $bulkUpdate;
        $this->importerFactory = $importerFactory;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Batch Processor.
     *
     * @param array $batch
     */
    public function process($batch): void
    {
        $this->addToImportQueue($batch);
        $this->resetOrderedProducts($batch);
        $this->markOrdersAsImported($batch);
    }

    /**
     * Register orders to importer.
     *
     * @param array $ordersBatch
     *
     * @return void
     */
    private function addToImportQueue(array $ordersBatch): void
    {
        foreach ($ordersBatch as $websiteId => $orders) {
            $success = $this->importerFactory->create()
                ->registerQueue(
                    Importer::IMPORT_TYPE_ORDERS,
                    $orders,
                    Importer::MODE_BULK,
                    $websiteId
                );
            if ($success) {
                $this->logger->info(
                    sprintf(
                        '%s orders synced for website id: %s',
                        count($orders),
                        $websiteId
                    )
                );
            }
        }
    }

    /**
     * Update products from orders.
     *
     * @param array $ordersBatch
     *
     * @return void
     */
    private function resetOrderedProducts($ordersBatch): void
    {
        foreach ($ordersBatch as $orders) {
            $this->bulkUpdate->execute($this->getAllProductsFromBatch($orders));
        }
    }

    /**
     * Update orders.
     *
     * @param array $ordersBatch
     *
     * @return void
     */
    private function markOrdersAsImported($ordersBatch)
    {
        $orderIds = $this->getOrderIdsFromIncrementIds(
            $this->getOrderIdsFromBatch($ordersBatch)
        );

        $this->orderResourceFactory->create()
            ->setImportedDateByIds($orderIds);
    }

    /**
     * Fetch products.
     *
     * @param \Dotdigitalgroup\Email\Model\Connector\Order[] $orders
     *
     * @return array
     */
    private function getAllProductsFromBatch($orders): array
    {
        $allProducts = [];
        foreach ($orders as $order) {
            if (! isset($order['products'])) {
                continue;
            }
            foreach ($order['products'] as $products) {
                $allProducts[] = $products;
            }
        }

        return $allProducts;
    }

    /**
     * Fetch order ids.
     *
     * @param array $ordersBatch
     *
     * @return array
     */
    private function getOrderIdsFromBatch(array $ordersBatch)
    {
        $ids = [];

        foreach ($ordersBatch as $ordersByWebsite) {
            foreach ($ordersByWebsite as $key => $data) {
                $ids[] = $key;
            }
        }

        return $ids;
    }

    /**
     * Get order ids from increment ids.
     *
     * @param array $incrementIds
     * @return array
     */
    private function getOrderIdsFromIncrementIds(array $incrementIds): array
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter(
                'main_table.increment_id',
                ['in' => $incrementIds]
            )
            ->getColumnValues('entity_id');
    }
}
