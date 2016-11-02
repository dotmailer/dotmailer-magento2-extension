<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

class SaveStatusSmsAutomation implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Store\Model\App\EmulationFactory
     */
    protected $_emulationFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    protected $_emailOrderFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    protected $_automationFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;

    /**
     * SaveStatusSmsAutomation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory             $automationFactory
     * @param \Dotdigitalgroup\Email\Model\OrderFactory                  $emailOrderFactory
     * @param \Magento\Framework\Registry                                $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface         $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManagerInterface
     * @param \Magento\Store\Model\App\EmulationFactory                  $emulationFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                         $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Store\Model\App\EmulationFactory $emulationFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_automationFactory = $automationFactory;
        $this->_emailOrderFactory = $emailOrderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManagerInterface;
        $this->_registry = $registry;
        $this->_emulationFactory = $emulationFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_helper = $data;
    }

    /**
     * Save/reset the order as transactional data.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $status = $order->getStatus();
            $storeId = $order->getStoreId();
            $store = $this->_storeManager->getStore($storeId);
            $storeName = $store->getName();
            $websiteId = $store->getWebsiteId();
            $customerEmail = $order->getCustomerEmail();
            // start app emulation
            $appEmulation = $this->_emulationFactory->create();
            $initialEnvironmentInfo
                          = $appEmulation->startEnvironmentEmulation($storeId);
            $emailOrder = $this->_emailOrderFactory->create()
                ->loadByOrderId($order->getEntityId(), $order->getQuoteId());
            //reimport email order
            $emailOrder->setUpdatedAt($order->getUpdatedAt())
                ->setCreatedAt($order->getUpdatedAt())
                ->setStoreId($storeId)
                ->setOrderStatus($status);
            if ($emailOrder->getEmailImported()
                != \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED
            ) {
                $emailOrder->setEmailImported(null);
            }

            //if api is not enabled
            if (!$store->getWebsite()
                ->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED)
            ) {
                return $this;
            }

            // check for order status change
            $statusBefore
                = $this->_registry->registry('sales_order_status_before');
            if ($status != $statusBefore) {
                //If order status has changed and order is already imported then set modified to 1
                if ($emailOrder->getEmailImported()
                    == \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED
                ) {
                    $emailOrder->setModified(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED);
                }
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
            if (!empty($configStatusAutomationMap)) {
                foreach ($configStatusAutomationMap as $configMap) {
                    if ($configMap['status'] == $status) {
                        //send to automation queue
                        $this->_doAutomationEnrolment(
                            [
                                'programId' => $configMap['automation'],
                                'automationType' => 'order_automation_' . $status,
                                'email' => $customerEmail,
                                'order_id' => $order->getId(),
                                'website_id' => $websiteId,
                                'store_name' => $storeName
                            ]
                        );
                    }
                }
            }
            //If customer's first order
            if ($order->getCustomerId()) {
                $orders = $this->_orderCollectionFactory->create()
                    ->addFieldToFilter('customer_id', $order->getCustomerId());
                if ($orders->getSize() == 1) {
                    $automationTypeNewOrder
                        = \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER;
                    $programIdNewOrder = $this->_helper->getAutomationIdByType(
                        'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_FIRST_ORDER', $order->getWebsiteId()
                    );
                    //send to automation queue
                    $this->_doAutomationEnrolment(
                        [
                            'programId' => $programIdNewOrder,
                            'automationType' => $automationTypeNewOrder,
                            'email' => $customerEmail,
                            'order_id' => $order->getId(),
                            'website_id' => $websiteId,
                            'store_name' => $storeName
                        ]
                    );
                }
            }
            //admin oder when editing the first one is canceled
            $this->_registry->unregister('sales_order_status_before');
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * save enrolment to queue for cron automation enrolment
     *
     * @param $data
     */
    protected function _doAutomationEnrolment($data)
    {
        //the program is not mapped
        if (!$data['programId']) {
            $this->_helper->log(
                'automation type : ' . $data['automationType'] . ' program id not found'
            );
        } else {
            try {
                $this->_automationFactory->create()
                    ->setEmail($data['email'])
                    ->setAutomationType($data['automationType'])
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($data['order_id'])
                    ->setWebsiteId($data['website_id'])
                    ->setStoreName($data['store_name'])
                    ->setProgramId($data['programId'])
                    ->save();
            } catch (\Exception $e) {
                $this->_helper->debug((string)$e, []);
            }
        }
    }
}
