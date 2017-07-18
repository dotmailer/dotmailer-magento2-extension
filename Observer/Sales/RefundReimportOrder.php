<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

/**
 * Reset the contact import on order refund.
 */
class RefundReimportOrder implements \Magento\Framework\Event\ObserverInterface
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
     * @var \Magento\Framework\Registry
     */
    private $_registry;

    /**
     * RefundReimportOrder constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource
     * @param \Magento\Framework\Registry               $registry
     * @param \Dotdigitalgroup\Email\Helper\Data        $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->emailOrderFactory = $emailOrderFactory;
        $this->orderResource = $orderResource;
        $this->helper            = $data;
        $this->_registry         = $registry;
    }

    /**
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
                ->loadByOrderId($orderId, $quoteId, $storeId);
            if (!$emailOrder->getId()) {
                $this->helper->log(
                    'ERROR Creditmemmo Order not found :'
                    . $orderId . ', quote id : ' . $quoteId . ', store id '
                    . $storeId
                );

                return $this;
            }

            $emailOrder->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED);
            $this->orderResource->save($emailOrder);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
