<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

class RefundReimportOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    protected $_emailOrderFactory;

    /**
     * RefundReimportOrder constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_emailOrderFactory = $emailOrderFactory;
        $this->_helper = $data;
        $this->_registry = $registry;
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
            $emailOrder = $this->_emailOrderFactory->create()
                ->loadByOrderId($orderId, $quoteId, $storeId);
            if (!$emailOrder->getId()) {
                $this->_helper->log('ERROR Creditmemmo Order not found :'
                    . $orderId . ', quote id : ' . $quoteId . ', store id '
                    . $storeId);

                return $this;
            }

            $emailOrder->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED)
                ->save();
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, []);
        }

        return $this;
    }
}
