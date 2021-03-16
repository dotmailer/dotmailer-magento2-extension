<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Model\Connector\Product;

class Exporter
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\ProductFactory
     */
    private $connectorProductFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    private $catalogCollectionFactory;

    /**
     * Catalog constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\ProductFactory $connectorProductFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory,
        \Dotdigitalgroup\Email\Model\Connector\ProductFactory $connectorProductFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper
    ) {
        $this->catalogCollectionFactory = $catalogCollectionFactory;
        $this->connectorProductFactory = $connectorProductFactory;
        $this->helper = $helper;
    }

    /**
     * @param $storeId
     * @param $productsToProcess
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function exportCatalog($storeId, $productsToProcess)
    {
        $connectorProducts = [];
        $products = $this->getProductsToExport($storeId, $productsToProcess);

        foreach ($products as $product) {
            $connectorProduct = $this->connectorProductFactory->create()
                ->setProduct($product, $storeId);
            $connectorProducts[$product->getId()] = $this->expose($connectorProduct);
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
