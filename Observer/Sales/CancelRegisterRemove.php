<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Sales;


class CancelRegisterRemove implements \Magento\Framework\Event\ObserverInterface
{

	protected $_helper;
	protected $_registry;
	protected $_logger;
	protected $_scopeConfig;
	protected $_storeManager;
	protected $_objectManager;
	protected $_orderFactory;


	public function __construct(
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Psr\Log\LoggerInterface $loggerInterface,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
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
		$order = $observer->getEvent()->getOrder();
		$incrementId = $order->getIncrementId();
		$websiteId = $this->_storeManager->getStore($order->getStoreId())->getWebsiteId();

		$orderSync = $this->_helper->getOrderSyncEnabled($websiteId);

		if ($this->_helper->isEnabled($websiteId) && $orderSync) {
			//register in queue with importer
			$this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
				\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_ORDERS,
				array($incrementId),
				\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
				$websiteId
			);
		}

		return $this;
	}
}
