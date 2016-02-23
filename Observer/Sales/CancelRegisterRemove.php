<?php


namespace Dotdigitalgroup\Email\Observer\Sales;


class CancelRegisterRemove implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helper;
    protected $_registry;
    protected $_logger;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_orderFactory;
    protected $_proccessorFactory;

    /**
     * CancelRegisterRemove constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ProccessorFactory     $proccessorFactory
     * @param \Magento\Sales\Model\OrderFactory                  $orderFactory
     * @param \Magento\Framework\Registry                        $registry
     * @param \Dotdigitalgroup\Email\Helper\Data                 $data
     * @param \Psr\Log\LoggerInterface                           $loggerInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ProccessorFactory $proccessorFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_proccessorFactory = $proccessorFactory;
        $this->_helper            = $data;
        $this->_orderFactory      = $orderFactory;
        $this->_scopeConfig       = $scopeConfig;
        $this->_logger            = $loggerInterface;
        $this->_storeManager      = $storeManagerInterface;
        $this->_registry          = $registry;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order       = $observer->getEvent()->getOrder();
        $incrementId = $order->getIncrementId();
        $websiteId   = $this->_storeManager->getStore($order->getStoreId())
            ->getWebsiteId();

        $orderSync = $this->_helper->getOrderSyncEnabled($websiteId);

        if ($this->_helper->isEnabled($websiteId) && $orderSync) {
            //register in queue with importer
            $this->_proccessorFactory->create()
                ->registerQueue(
                    \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_ORDERS,
                    array($incrementId),
                    \Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
                    $websiteId
                );
        }

        return $this;
    }
}
