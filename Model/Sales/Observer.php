<?php

namespace Dotdigitalgroup\Email\Model\Sales;

class Observer
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
    /**
     * Register the order status.
     * @param $observer
     * @return $this
     */
    public function handleSalesOrderSaveBefore(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
	    //order is new
	    if (! $order->getId()) {
		    $orderStatus = $order->getStatus();
	    } else {
		    // the reloaded status
		    $reloaded = $this->_orderFactory->create()
		        ->load( $order->getId() );
		    $orderStatus = $reloaded->getStatus();
	    }
	    //register the order status before change
        if (! $this->_registry->registry('sales_order_status_before'))
            $this->_registry->register('sales_order_status_before', $orderStatus);

	    return $this;
    }

	/**
	 * save/reset the order as transactional data.
	 * @param $observer
	 *
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    public function handleSalesOrderSaveAfter($observer)
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


	/**
	 * Create new order event.
	 *
	 * @param $observer
	 *
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    public function handleSalesOrderPlaceAfter($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $email      = $order->getCustomerEmail();
        $website    = $this->_storeManager->getWebsite($order->getWebsiteId());
        $storeName  = $this->_storeManager->getStore($order->getStoreId())->getName();
        //if api is not enabled
        if (! $this->_helper->isEnabled($website))
            return $this;
        //automation enrolment for order
        if($order->getCustomerIsGuest()){
            // guest to automation mapped
            $programType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_GUEST_ORDER';
            $automationType = \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_GUEST_ORDER;
        } else {
            // customer to automation mapped
            $programType = 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER';
            $automationType = \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_ORDER;
        }
        $programId = $this->_helper->getAutomationIdByType($programType, $order->getWebsiteId());

        //the program is not mapped
        if (! $programId){
            return $this;
        }
        try {
            $automation = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Automation');
            $automation->setEmail( $email )
                ->setAutomationType( $automationType )
                ->setEnrolmentStatus( \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING )
                ->setTypeId( $order->getId() )
                ->setWebsiteId( $website->getId() )
                ->setStoreName( $storeName )
                ->setProgramId( $programId )
                ->save();
        }catch(\Exception $e){
	        $this->_helper->debug((string)$e, array());
        }

        return $this;
    }

    /**
     * Sales order refund event.
     *
     * @return $this
     */
    public function handleSalesOrderRefund($observer)
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
            $emailOrder = $this->_objectManager->create('Dotdigitalgroup\Email\Order')->loadByOrderId($orderId, $quoteId, $storeId);
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

    /**
     * Sales cancel order event, remove transactional data.
     *
     * @return $this
     */
    public function hangleSalesOrderCancel( $observer)
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

    /**
     * convert_quote_to_order observer
     *
     * @return $this
     */
    public function handleQuoteToOrder( $observer)
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
			    $connectorQuote = $this->_objectManager->create( 'Dotdigitalgroup\Email\Model\Quote' )->loadQuote( $quoteId );
			    if ( $connectorQuote ) {
				    //register in queue with importer for single delete
				    $this->_objectManager->create( 'Dotdigitalgroup\Email\Model\Proccessor' )->registerQueue(
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

	/**
	 * @param $observer
	 *
	 * @return $this
	 */
    public function handleQuoteSaveAfter($observer)
    {
        $quote = $observer->getEvent()->getQuote();
	    $websiteId = $quote->getStore()->getWebsiteId();
	    $apiEnabled = $this->_helper->isEnabled($websiteId);
        $syncEnabled = $this->_helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_QUOTE_ENABLED,
            $websiteId
        );
        if ($apiEnabled && $syncEnabled) {
            if ($quote->getCustomerId()) {
                $connectorQuote = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Quote')->loadQuote($quote->getId());
                $count = count($quote->getAllItems());
                if ($connectorQuote) {
                    if ($connectorQuote->getImported() && $count > 0)
                        $connectorQuote->setModified(1)->save();
                    elseif ($connectorQuote->getImported() && $count == 0) {
                        //register in queue with importer for single delete
	                    $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
		                    \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_QUOTE,
		                    array($connectorQuote->getQuoteId()),
		                    \Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
		                    $websiteId
	                    );

                        $connectorQuote->delete();
                    }
                } elseif ($count > 0)
                    $this->_registerQuote($quote);
            }
        }
        return $this;
    }

    /**
     * register quote with connector
     *
     */
    private function _registerQuote( $quote)
    {
        try {
            $connectorQuote = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Quote');
            $connectorQuote->setQuoteId($quote->getId())
                ->setCustomerId($quote->getCustomerId())
                ->setStoreId($quote->getStoreId())
                ->save();
        }catch (\Exception $e){
	        $this->_helper->debug((string)$e, array());

        }
    }
}