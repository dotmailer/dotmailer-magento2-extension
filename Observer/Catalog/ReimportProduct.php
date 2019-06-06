<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

/**
 * Product to be marked as modified and reimported.
 */
class ReimportProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog
     */
    private $updater;

    /**
     * ReimportProduct constructor.
     * @param  \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog $updater
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog $updater
    ) {
        $this->updater = $updater;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productModel = $observer->getEvent()->getDataObject();
        $this->updater->execute($productModel->getId());
    }
}
