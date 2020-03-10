<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Dotdigitalgroup\Email\Api\StockFinderInterface;
use Dotdigitalgroup\Email\Logger\Logger;

class StockFinder implements StockFinderInterface
{
    /**
     * @var StockItemRepository
     */
    private $stockItemRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * StockFinder constructor.
     * @param StockItemRepository $stockItemRepository
     * @param Logger $logger
     */
    public function __construct(
        StockItemRepository $stockItemRepository,
        Logger $logger
    ) {
        $this->stockItemRepository = $stockItemRepository;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return float|\Magento\CatalogInventory\Api\Data\StockItemInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStockQty($product)
    {
        switch ($product->getTypeId()) {
            case 'configurable':
                return $this->getStockQtyForConfigurableProducts($product);
            default:
                return $this->stockItemRepository->get($product->getId())->getQty();
        }
    }

    /**
     * @param $configurableProduct
     * @return float
     */
    private function getStockQtyForConfigurableProducts($configurableProduct)
    {
        $simpleProducts = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);
        $stockQty = 0;
        foreach ($simpleProducts as $product) {
            try {
                $stockQty += $this->stockItemRepository->get($product->getId())->getQty();
            } catch (\Exception $e) {
                $this->logger->debug((string) $e);
            }
        }
        return $stockQty;
    }
}
