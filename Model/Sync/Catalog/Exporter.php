<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

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
     * Export catalog.
     *
     * @param string|int|null $storeId
     * @param string|int $limit
     *
     * @return array
     */
    public function exportCatalog($storeId, $limit)
    {
        $connectorProducts = [];
        $products = $this->getProductsToExport($storeId, $limit);

        foreach ($products as $product) {
            $connectorProduct = $this->connectorProductFactory->create()
                ->setProduct($product, $storeId);
            $connectorProducts[$product->getId()] = $connectorProduct->expose();
        }

        return $connectorProducts;
    }

    /**
     * Get product collection to export.
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @param int $limit
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|array
     */
    private function getProductsToExport($store, $limit)
    {
        return $this->catalogCollectionFactory->create()
            ->getProductsToExportByStore($store, $limit);
    }
}
