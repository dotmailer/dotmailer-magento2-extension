<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

/**
 * Reset the contact import on order refund.
 */
class OrderCreditmemoSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order
     */
    private $orderResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    private $emailOrderFactory;

    /**
     * RefundReimportOrder constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->emailOrderFactory = $emailOrderFactory;
        $this->orderResource = $orderResource;
        $this->helper            = $data;
    }

    /**
     * Execute action.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $storeId = $creditmemo->getStoreId();
        $order = $creditmemo->getOrder();
        $orderId = $order->getEntityId();
        $quoteId = $order->getQuoteId();

        try {
            /*
             * Reimport transactional data.
             */
            $emailOrder = $this->emailOrderFactory->create()
                ->loadOrCreateOrder($orderId, $quoteId);
            if (!$emailOrder->getId()) {
                $this->helper->log(
                    'ERROR Creditmemo Order not found :'
                    . $orderId . ', quote id : ' . $quoteId . ', store id '
                    . $storeId
                );

                return $this;
            }

            $emailOrder->setProcessed(0);
            $this->orderResource->save($emailOrder);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
