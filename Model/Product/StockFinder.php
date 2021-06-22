<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\StockFinderInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\Stock\SalableQuantity;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

class StockFinder implements StockFinderInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SalableQuantity
     */
    private $salableQuantity;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * StockFinder constructor.
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param Logger $logger
     * @param SalableQuantity $salableQuantity
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param GetAssignedStockIdsBySku $getAssignedStockIdsBySku
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannelsForStock
     * @param ScopeConfigInterface $scopeConfig
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        Logger $logger,
        SalableQuantity $salableQuantity,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        SourceItemRepositoryInterface $sourceItemRepository
    ) {
        $this->logger = $logger;
        $this->salableQuantity = $salableQuantity;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->sourceItemRepository = $sourceItemRepository;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $websiteId
     * @return float|\Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function getStockQty($product, int $websiteId)
    {
        try {
            switch ($product->getTypeId()) {
                case 'configurable':
                    return $this->getStockQtyForConfigurableProduct($product, $websiteId);
                default:
                    return $this->getStockQtyForProducts([$product], $websiteId);
            }
        } catch (\Exception $e) {
            $this->logger->debug(
                'Stock qty not found for ' . $product->getTypeId() . ' product id ' . $product->getId(),
                [(string) $e]
            );
            return 0;
        }
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $configurableProduct
     * @param int $websiteId
     * @return float|int
     */
    private function getStockQtyForConfigurableProduct($configurableProduct, $websiteId)
    {
        $simpleProducts = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);
        return $this->getStockQtyForProducts($simpleProducts, $websiteId);
    }

    /**
     * Calculate available stock for an array of products.
     *
     * If Manage Stock is disabled globally, or disabled for individual SKUs,
     * or if catalog sync is running at default level, we cannot use
     * salable quantity (stock is allocated to website sales channels).
     * In these cases we fall back to the 'Quantity per Source'.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     * @param int $websiteId
     * @return float|int
     */
    private function getStockQtyForProducts($products, $websiteId)
    {
        $qty = 0;
        $skusWithNotManageStock = [];

        $manageStock = $this->scopeConfig->getValue(
            Configuration::XML_PATH_MANAGE_STOCK
        );

        if (!$manageStock || $websiteId === 0) {
            foreach ($products as $product) {
                $skusWithNotManageStock[] = $product->getSku();
            }
        } else {
            foreach ($products as $product) {
                try {
                    $qty += $this->salableQuantity->getSalableQuantity($product, $websiteId);
                } catch (\Exception $e) {
                    $skusWithNotManageStock[] = $product->getSku();
                    continue;
                }
            }
        }

        if ($skusWithNotManageStock) {
            $sourceItems = $this->loadInventorySourceItems($skusWithNotManageStock);
            foreach ($sourceItems as $item) {
                $qty += $item->getQuantity();
            }
        }

        return $qty;
    }

    /**
     * @param array $skus
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    private function loadInventorySourceItems($skus)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $skus, 'in')
            ->create();

        return $this->sourceItemRepository->getList($searchCriteria)->getItems();
    }
}
