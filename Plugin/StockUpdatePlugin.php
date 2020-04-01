<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog;
use Dotdigitalgroup\Email\Model\Catalog\CatalogService;

class StockUpdatePlugin
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var UpdateCatalog
     */
    private $catalogUpdater;

    /**
     * @var CatalogService
     */
    private $catalogService;

    /**
     * StockUpdatePlugin constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param UpdateCatalog $catalogUpdater
     * @param CatalogService $catalogService
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        UpdateCatalog $catalogUpdater,
        CatalogService $catalogService
    ) {
        $this->productRepository = $productRepository;
        $this->catalogUpdater = $catalogUpdater;
        $this->catalogService = $catalogService;
    }

    /**
     * @param StockRegistryInterface $subject
     * @param $result
     * @param string $productSku
     *
     * @return mixed
     */
    public function afterUpdateStockItemBySku(
        StockRegistryInterface $subject,
        $result,
        $productSku
    ) {
        try {
            $product = $this->productRepository->get($productSku);
            $this->catalogUpdater->execute($product);
            $this->catalogService->setIsCatalogUpdated();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $result;
        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            return $result;
        }

        return $result;
    }
}
