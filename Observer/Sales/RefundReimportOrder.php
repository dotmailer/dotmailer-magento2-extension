<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Sales;


class RefundReimportOrder implements \Magento\Framework\Event\ObserverInterface
{

	protected $_helper;
	protected $_registry;
	protected $_logger;
	protected $_scopeConfig;
	protected $_storeManager;
	protected $_objectManager;
	protected $_orderFactory;
	protected $_emailOrderFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Psr\Log\LoggerInterface $loggerInterface,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_emailOrderFactory = $emailOrderFactory;
		$this->_helper = $data;
		$this->_orderFactory = $orderFactory;
		$this->_scopeConfig = $scopeConfig;
		$this->_logger = $loggerInterface;
		$this->_storeManager = $storeManagerInterface;
		$this->_registry = $registry;
		$this->_objectManager = $objectManagerInterface;
	}


	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$creditmemo = $observer->getEvent()->getCreditmemo();
		$storeId = $creditmemo->getStoreId();
		$order   = $creditmemo->getOrder();
		$orderId = $order->getEntityId();
		$quoteId = $order->getQuoteId();

		try{
			/**
			 * Reimport transactional data.
			 */
			$emailOrder = $this->_emailOrderFactory->create()
				->loadByOrderId($orderId, $quoteId, $storeId);
			if (!$emailOrder->getId()) {
				$this->_helper->log('ERROR Creditmemmo Order not found :' . $orderId . ', quote id : ' . $quoteId . ', store id ' . $storeId);
				return $this;
			}

			$emailOrder->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED)
			           ->save();
		}catch (\Exception $e){
			$this->_helper->debug((string)$e, array());

		}

		return $this;
	}
}
