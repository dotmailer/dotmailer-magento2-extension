<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

/**
 * Save original order status.
 */
class SaveRegisterOrderStatusBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;
    
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    private $orderResource;

    /**
     * SaveRegisterOrderStatusBefore constructor.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->orderResource = $orderResource;
        $this->orderFactory = $orderFactory;
        $this->registry     = $registry;
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
            $reloaded = $this->orderFactory->create();
            $this->orderResource->load($reloaded, $order->getId());
            $orderStatus = $reloaded->getStatus();
        }
        //register the order status before change
        if (!$this->registry->registry('sales_order_status_before')) {
            $this->registry->register(
                'sales_order_status_before',
                $orderStatus
            );
        }

        return $this;
    }
}
