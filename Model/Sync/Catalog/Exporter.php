<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Model\Connector\Product;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ProductFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use Magento\Framework\Exception\ValidatorException;

class Exporter
{
    /**
     * @var ProductFactory
     */
    private $connectorProductFactory;

    /**
     * @var CollectionFactory
     */
    private $catalogCollectionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param CollectionFactory $catalogCollectionFactory
     * @param ProductFactory $connectorProductFactory
     * @param Logger $logger
     */
    public function __construct(
        CollectionFactory $catalogCollectionFactory,
        ProductFactory $connectorProductFactory,
        Logger $logger
    ) {
        $this->catalogCollectionFactory = $catalogCollectionFactory;
        $this->connectorProductFactory = $connectorProductFactory;
        $this->logger = $logger;
    }

    /**
     * Export catalog
     *
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
                $connectorProduct = $this->connectorProductFactory->create();
                $connectorProduct->setProduct($product, $storeId);
                $connectorProducts[$product->getId()] = $connectorProduct->toArray();
            } catch (SchemaValidationException $exception) {
                $this->logger->debug(
                    sprintf(
                        "Product id %s was not exported, but will be marked as processed.",
                        $product->getId()
                    ),
                    [$exception, $exception->errors()]
                );
            } catch (\Exception $exception) {
                $this->logger->debug(
                    sprintf(
                        'Product id %s was not exported, but will be marked as processed.',
                        $product->getId()
                    ),
                    [$exception]
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
}
