<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

class CancelRegisterRemove implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

    /**
     * CancelRegisterRemove constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_importerFactory = $importerFactory;
        $this->_helper = $data;
        $this->_storeManager = $storeManagerInterface;
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
        $order = $observer->getEvent()->getOrder();
        $incrementId = $order->getIncrementId();
        $websiteId = $this->_storeManager->getStore($order->getStoreId())
            ->getWebsiteId();

        $orderSync = $this->_helper->isOrderSyncEnabled($websiteId);

        if ($this->_helper->isEnabled($websiteId) && $orderSync) {
            //register in queue with importer
            $this->_importerFactory->create()
                ->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
                    [$incrementId],
                    \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                    $websiteId
                );
        }

        return $this;
    }
}
