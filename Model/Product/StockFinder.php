<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Dotdigitalgroup\Email\Api\StockFinderInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Dotdigitalgroup\Email\Logger\Logger;

class StockFinder implements StockFinderInterface
{
    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteria;

    /**
     * StockFinder constructor.
     *
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param Logger $logger
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteria
     */
    public function __construct(
        StockItemRepositoryInterface $stockItemRepository,
        Logger $logger,
        StockItemCriteriaInterfaceFactory $stockItemCriteria
    ) {
        $this->stockItemRepository = $stockItemRepository;
        $this->logger = $logger;
        $this->stockItemCriteria = $stockItemCriteria;
    }

    /**
     * Get Stock Quantity
     *
     * @param Product $product
     * @param int $websiteId
     * @return float|int
     */
    public function getStockQty($product, int $websiteId)
    {
        try {
            switch ($product->getTypeId()) {
                case 'configurable':
                    return $this->getStockQtyForConfigurableProduct($product);
                default:
                    return $this->getStockQtyForProducts([$product]);
            }
        } catch (\Exception $e) {
            $this->logger->debug(
                'Stock qty not found for ' . $product->getTypeId() . ' product id ' . $product->getId(),
                [(string) $e]
            );
        }
        return 0;
    }

    /**
     * Get stock for configurable product
     *
     * @param Product $configurableProduct
     * @return float|int
     */
    private function getStockQtyForConfigurableProduct(ProductInterface $configurableProduct)
    {
        $configurableProductInstance = $configurableProduct->getTypeInstance();
        /** @var Configurable $configurableProductInstance */
        $simpleProducts = $configurableProductInstance->getUsedProducts($configurableProduct);
        return $this->getStockQtyForProducts($simpleProducts);
    }

    /**
     * Get stock for products
     *
     * @param ProductInterface[] $products
     * @return float|int
     */
    private function getStockQtyForProducts($products)
    {
        $stock = 0;
        $searchCriteria = $this->stockItemCriteria->create();
        $searchCriteria->setProductsFilter($products);
        $stockItemsRepository = $this->stockItemRepository->getList($searchCriteria);
        $stockItemsCollection = $stockItemsRepository->getItems();

        foreach ($stockItemsCollection as $product) {
            $stock += $product->getQty();
        }

        return $stock;
    }
}
