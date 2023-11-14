<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

class ReimportBunch implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk
     */
    private $bulkUpdater;

    /**
     * ReimportBunch constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk $updater
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk $updater
    ) {
        $this->bulkUpdater = $updater;
    }

    /**
     * Execute.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $bunch = $observer->getBunch();
        $this->bulkUpdater->execute($bunch);
    }
}
