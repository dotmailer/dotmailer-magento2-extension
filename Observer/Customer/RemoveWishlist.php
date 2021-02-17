<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Wishlist delete.
 */
class RemoveWishlist implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * RemoveWishlist constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->importerFactory = $importerFactory;
        $this->helper          = $data;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $wishlist = $observer->getEvent()->getDataObject();
            $websiteId = $this->storeManager->getWebsite()->getId();
            $isEnabled = $this->helper->isEnabled($websiteId);
            $syncEnabled = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
                $websiteId
            );

            if ($isEnabled && $syncEnabled && $wishlist->getId()) {
                $this->importerFactory->create()->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_WISHLIST,
                    [$wishlist->getId()],
                    \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                    $websiteId
                );
            }
        } catch (\Exception $e) {
            $this->logger->debug((string) $e);
        }
    }
}
