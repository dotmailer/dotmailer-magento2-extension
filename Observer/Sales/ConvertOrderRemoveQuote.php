<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Sales;


class ConvertOrderRemoveQuote implements \Magento\Framework\Event\ObserverInterface
{

	protected $_helper;
	protected $_registry;
	protected $_logger;
	protected $_scopeConfig;
	protected $_storeManager;
	protected $_objectManager;
	protected $_orderFactory;
	protected $_quoteFactory;
	protected $_proccessorFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\ProccessorFactory $proccessorFactory,
		\Dotdigitalgroup\Email\Model\QuoteFactory $quoteFactory,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Psr\Log\LoggerInterface $loggerInterface,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_proccessorFactory = $proccessorFactory;
		$this->_quoteFactory = $quoteFactory;
		$this->_helper = $data;
		$this->_orderFactory = $orderFactory;
		$this->_scopeConfig = $scopeConfig;
		$this->_logger = $loggerInterface;
		$this->_storeManager = $storeManagerInterface;
		$this->_registry = $registry;
		$this->_objectManager = $objectManagerInterface;
	}


	/**
	 * Converting order will remove the email quote.
	 *
	 * @param \Magento\Framework\Event\Observer $observer
	 *
	 * @return $this
	 */
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		try {
			$order       = $observer->getOrder();
			$websiteId   = $order->getStore()->getWebsiteId();
			$apiEnabled  = $this->_helper->isEnabled( $websiteId );
			$syncEnabled = $this->_helper->getWebsiteConfig(
				\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED,
				$websiteId
			);
			if ( $apiEnabled && $syncEnabled ) {
				$quoteId        = $order->getQuoteId();
				$connectorQuote = $this->_quoteFactory->create()
					->loadQuote( $quoteId );
				if ( $connectorQuote ) {
					//register in queue with importer for single delete
					$this->_proccessorFactory->create()
						->registerQueue(
							\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_QUOTE,
							array( $connectorQuote->getQuoteId() ),
							\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
							$order->getStore()->getWebsiteId()
					);
					//delete from table
					$connectorQuote->delete();
				}
			}
		}catch(\Exception $e){
			$this->_helper->debug((string)$e, array());
		}

		return $this;
	}
}
