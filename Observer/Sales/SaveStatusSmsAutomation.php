<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Sales;


class SaveStatusSmsAutomation implements \Magento\Framework\Event\ObserverInterface
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
		try{
			$order = $observer->getEvent()->getOrder();
			$status  = $order->getStatus();
			$storeId = $order->getStoreId();
			$store = $this->_storeManager->getStore($storeId);
			$storeName = $store->getName();
			$websiteId = $store->getWebsiteId();
			$customerEmail = $order->getCustomerEmail();
			// start app emulation
			$appEmulation = $this->_objectManager->create('Magento\Store\Model\App\Emulation');
			$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
			$emailOrder = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Order')->loadByOrderId($order->getEntityId(), $order->getQuoteId());
			//reimport email order
			$emailOrder->setUpdatedAt($order->getUpdatedAt())
			           ->setStoreId($storeId)
			           ->setOrderStatus($status);
			if ($emailOrder->getEmailImported() != \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED) {
				$emailOrder->setEmailImported(null);
			}

			//if api is not enabled
			if (!$store->getWebsite()->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED))
				return $this;

			// check for order status change
			$statusBefore =  $this->_registry->registry('sales_order_status_before');
			if ( $status!= $statusBefore) {
				//If order status has changed and order is already imported then set modified to 1
				if($emailOrder->getEmailImported() == \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED) {
					$emailOrder->setModified(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED);
				}
				$smsCampaign = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Sms\Campaign');
				$smsCampaign->setOrder($order);
				$smsCampaign->setStatus($status);
				$smsCampaign->sendSms();
			}
			// set back the current store
			$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
			$emailOrder->save();

			//Status check automation enrolment
			$configStatusAutomationMap = unserialize(
				$this->_scopeConfig->getValue(
					\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS,
					\Magento\Store\Model\ScopeInterface::SCOPE_STORE,
					$order->getStore()));
			if(!empty($configStatusAutomationMap)){
				foreach($configStatusAutomationMap as $configMap){
					if($configMap['status'] == $status) {
						try {
							$programId  = $configMap['automation'];
							$automation = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Automation');
							$automation->setEmail( $customerEmail )
							           ->setAutomationType( 'order_automation_' . $status )
							           ->setEnrolmentStatus( \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING )
							           ->setTypeId( $order->getId() )
							           ->setWebsiteId( $websiteId )
							           ->setStoreName( $storeName )
							           ->setProgramId( $programId );
							$automation->save();
						}catch(\Exception $e){
						}
					}
				}
			}
			//admin oder when editing the first one is canceled
			$this->_registry->unregister('sales_order_status_before');
		}catch(\Exception $e){
			$this->_helper->debug((string)$e, array());
		}
		return $this;
	}
}
