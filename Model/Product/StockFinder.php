<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Dotdigitalgroup\Email\Api\StockFinderInterface;
use Dotdigitalgroup\Email\Logger\Logger;

class StockFinder implements StockFinderInterface
{
    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * StockFinder constructor.
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockRegistryInterface $stockRegistry
     * @param Logger $logger
     */
    public function __construct(
        StockItemRepositoryInterface $stockItemRepository,
        StockRegistryInterface $stockRegistry,
        Logger $logger
    ) {
        $this->stockItemRepository = $stockItemRepository;
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
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
                    return $this->getStockQtyForProduct($product);
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
     * @return float
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStockQtyForConfigurableProduct($configurableProduct)
    {
        $simpleProducts = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);
        $stockQty = 0;
        foreach ($simpleProducts as $product) {
            $stockQty += $this->getStockQtyForProduct($product);
        }
        return $stockQty;
    }

    /**
     * @param $product
     * @return float
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStockQtyForProduct($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        return $this->stockItemRepository->get(
            $stockItem->getItemId()
        )->getQty();
    }
}
