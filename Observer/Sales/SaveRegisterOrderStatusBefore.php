<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

class SaveRegisterOrderStatusBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * SaveRegisterOrderStatusBefore constructor.
     *
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->_orderFactory = $orderFactory;
        $this->_registry = $registry;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        //order is new
        if (!$order->getId()) {
            $orderStatus = $order->getStatus();
        } else {
            // the reloaded status
            $reloaded = $this->_orderFactory->create()
                ->load($order->getId());
            $orderStatus = $reloaded->getStatus();
        }
        //register the order status before change
        if (!$this->_registry->registry('sales_order_status_before')) {
            $this->_registry->register(
                'sales_order_status_before',
                $orderStatus
            );
        }

        return $this;
    }
}
