<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

use Dotdigitalgroup\Email\Model\Catalog\CatalogService;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Product to be marked as unprocessed and reimported.
 */
class ReimportProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog
     */
    private $updater;

    /**
     * @var CatalogService
     */
    private $catalogService;

    /**
     * ReimportProduct constructor.
     *
     * @param  \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog $updater
     * @param CatalogService $catalogService
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog $updater,
        CatalogService $catalogService
    ) {
        $this->updater = $updater;
        $this->catalogService = $catalogService;
    }

    /**
     * Execute.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->catalogService->isCatalogUpdated()) {
            return $this;
        }
        try {
            $productModel = $observer->getEvent()->getProduct();
            $this->updater->execute($productModel);
        } catch (AlreadyExistsException $e) {
            return $this;
        }
        return $this;
    }
}
