<?php

namespace Dotdigitalgroup\Email\Model\Product;

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
     * @param \Magento\Catalog\Model\Product $product
     * @return float|\Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function getStockQty($product)
    {
        try {
            switch ($product->getTypeId()) {
                case 'configurable':
                    return $this->getStockQtyForConfigurableProduct($product);
                default:
                    return $this->getStockQtyForProducts($product);
            }
        } catch (\Exception $e) {
            $this->logger->debug(
                'Stock qty not found for ' . $product->getTypeId() . ' product id ' . $product->getId(),
                [(string) $e]
            );
        }
    }

    /**
     * @param $configurableProduct
     * @return float|int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStockQtyForConfigurableProduct($configurableProduct)
    {
        $simpleProducts = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);
        return $this->getStockQtyForProducts($simpleProducts);
    }

    /**
     * @param $products
     * @return float|int
     */
    private function getStockQtyForProducts($products)
    {
        $stock = 0;

        $searchCriteria = $this->stockItemCriteria->create();
        $searchCriteria->setProductsFilter($products);
        $stockProducts = $this->stockItemRepository->getList(
            $searchCriteria
        );

        if ($stockProducts->getSize()) {
            foreach ($stockProducts->getItems() as $product) {
                $stock += $product->getQty();
            }
        }

        return $stock;
    }
}
