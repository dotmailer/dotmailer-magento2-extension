<?php


namespace Dotdigitalgroup\Email\Observer\Sales;


class SaveRegisterOrderStatusBefore
    implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helper;
    protected $_registry;
    protected $_logger;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_orderFactory;


    /**
     * SaveRegisterOrderStatusBefore constructor.
     *
     * @param \Magento\Sales\Model\OrderFactory                  $orderFactory
     * @param \Magento\Framework\Registry                        $registry
     * @param \Dotdigitalgroup\Email\Helper\Data                 $data
     * @param \Psr\Log\LoggerInterface                           $loggerInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManagerInterface
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_helper       = $data;
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig  = $scopeConfig;
        $this->_logger       = $loggerInterface;
        $this->_storeManager = $storeManagerInterface;
        $this->_registry     = $registry;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        //order is new
        if ( ! $order->getId()) {
            $orderStatus = $order->getStatus();
        } else {
            // the reloaded status
            $reloaded    = $this->_orderFactory->create()
                ->load($order->getId());
            $orderStatus = $reloaded->getStatus();
        }
        //register the order status before change
        if ( ! $this->_registry->registry('sales_order_status_before')) {
            $this->_registry->register('sales_order_status_before',
                $orderStatus);
        }

        return $this;
    }
}
