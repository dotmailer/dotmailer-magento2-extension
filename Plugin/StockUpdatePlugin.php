<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog;
use Dotdigitalgroup\Email\Model\Catalog\CatalogService;
use Magento\Framework\App\State;

class StockUpdatePlugin
{
    /**
     * @var Data
     */
    private $helper;

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
     * @var State
     */
    private $state;

    /**
     * StockUpdatePlugin constructor.
     *
     * @param Data $helper
     * @param ProductRepositoryInterface $productRepository
     * @param UpdateCatalog $catalogUpdater
     * @param CatalogService $catalogService
     * @param State $state
     */
    public function __construct(
        Data $helper,
        ProductRepositoryInterface $productRepository,
        UpdateCatalog $catalogUpdater,
        CatalogService $catalogService,
        State $state
    ) {
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->catalogUpdater = $catalogUpdater;
        $this->catalogService = $catalogService;
        $this->state = $state;
    }

    /**
     * Reset matching product when stock is updated.
     *
     * @param StockRegistryInterface $subject
     * @param string|int $result
     * @param string $productSku
     *
     * @return string|int
     */
    public function afterUpdateStockItemBySku(
        StockRegistryInterface $subject,
        $result,
        $productSku
    ) {
        if (!$this->helper->isEnabled() ||
            $this->state->getAreaCode() !== \Magento\Framework\App\Area::AREA_WEBAPI_REST) {
            return $result;
        }

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
