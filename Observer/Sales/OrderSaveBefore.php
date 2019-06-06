<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

/**
 * Save original order status.
 */
class OrderSaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Sales\Model\Spi\OrderResourceInterface
     */
    private $orderResource;

    /**
     * SaveRegisterOrderStatusBefore constructor.
     *
     * @param \Magento\Sales\Model\Spi\OrderResourceInterface $orderResource
     * @param \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Sales\Model\Spi\OrderResourceInterface $orderResource,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
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
        if (is_null($this->registry->registry('sales_order_status_before'))) {
            $this->registry->register(
                'sales_order_status_before',
                $orderStatus
            );
        }

        return $this;
    }
}
