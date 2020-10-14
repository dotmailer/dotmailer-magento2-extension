<?php

namespace Dotdigitalgroup\Email\Observer\Adminhtml;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Magento\Framework\Event\ObserverInterface;

class ImageTypes implements ObserverInterface
{
    /**
     * @var Catalog
     */
    private $catalogResource;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Catalog $catalogResource
     * @param Logger $logger
     */
    public function __construct(
        Catalog $catalogResource,
        Logger $logger
    ) {
        $this->catalogResource = $catalogResource;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();

        $changedPaths = (array) $event->getData('changed_paths');
        $isCatalogSyncChanged = in_array(Config::XML_PATH_CONNECTOR_IMAGE_TYPES_CATALOG_SYNC, $changedPaths);

        if ($isCatalogSyncChanged) {
            $this->catalogResource->resetCatalog();

            $this->logger->info('Catalog sync image type changed, catalog data reset.');
        }
    }
}
