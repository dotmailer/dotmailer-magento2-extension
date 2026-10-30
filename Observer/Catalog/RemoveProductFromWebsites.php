<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\Catalog;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Catalog\StoreLevelCatalogDeleter;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Remove a product from store-level collections of websites it was unassigned from.
 */
class RemoveProductFromWebsites implements ObserverInterface
{
    /**
     * @var StoreLevelCatalogDeleter
     */
    private $deleter;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param StoreLevelCatalogDeleter $deleter
     * @param Logger $logger
     */
    public function __construct(StoreLevelCatalogDeleter $deleter, Logger $logger)
    {
        $this->deleter = $deleter;
        $this->logger = $logger;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            $original = $product->getOrigData('website_ids');
            if (empty($original) || !is_array($original)) {
                return;
            }

            $removed = array_diff($original, (array) $product->getWebsiteIds());
            if ($removed) {
                $this->deleter->execute((int) $product->getId(), array_values($removed));
            }
        } catch (\Exception $e) {
            $this->logger->debug((string) $e);
        }
    }
}
