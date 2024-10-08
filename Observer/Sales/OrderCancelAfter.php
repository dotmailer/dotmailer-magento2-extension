<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Magento\Store\Model\StoreManagerInterface;

class OrderCancelAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @param ImporterFactory $importerFactory
     * @param Data $data
     * @param Logger $logger
     * @param StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        ImporterFactory $importerFactory,
        Data $data,
        Logger $logger,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->importerFactory = $importerFactory;
        $this->helper = $data;
        $this->logger = $logger;
        $this->storeManager = $storeManagerInterface;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $incrementId = $order->getIncrementId();
            $websiteId = $this->storeManager->getStore($order->getStoreId())
                ->getWebsiteId();

            $orderSync = $this->helper->isOrderSyncEnabled($websiteId);

            if ($this->helper->isEnabled($websiteId) && $orderSync) {
                //register in queue with importer
                $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
                        [$incrementId],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                        $websiteId
                    );
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in Order CancelAfter observer', [(string) $e]);
        }

        return $this;
    }
}
