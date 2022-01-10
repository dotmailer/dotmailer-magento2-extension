<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Model\Connector\Product;
use Dotdigitalgroup\Email\Logger\Logger;

class Exporter
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\ProductFactory
     */
    private $connectorProductFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    private $catalogCollectionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\ProductFactory $connectorProductFactory
     * @param Logger $logger
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory,
        \Dotdigitalgroup\Email\Model\Connector\ProductFactory $connectorProductFactory,
        Logger $logger
    ) {
        $this->catalogCollectionFactory = $catalogCollectionFactory;
        $this->connectorProductFactory = $connectorProductFactory;
        $this->logger = $logger;
    }

    /**
     * @param string|int $storeId
     * @param array $productsToProcess
     * @return array
     */
    public function exportCatalog($storeId, $productsToProcess)
    {
        $connectorProducts = [];
        $products = $this->getProductsToExport($storeId, $productsToProcess);

        foreach ($products as $product) {
            try {
                $connectorProduct = $this->connectorProductFactory->create()
                    ->setProduct($product, $storeId);
                $connectorProducts[$product->getId()] = $this->expose($connectorProduct);
            } catch (\Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Product id %s was not exported, but will be marked as processed.',
                        $product->getId()
                    ),
                    [(string) $e]
                );
            }
        }

        return $connectorProducts;
    }

    /**
     * Get product collection to export.
     *
     * @param string $storeId
     * @param array $productIds
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|array
     */
    private function getProductsToExport($storeId, $productIds)
    {
        return $this->catalogCollectionFactory->create()
            ->filterProductsByStoreTypeAndVisibility($storeId, $productIds);
    }

    /**
     * @param Product $connectorProduct
     * @return array
     */
    private function expose($connectorProduct)
    {
        return get_object_vars($connectorProduct);
    }
}
