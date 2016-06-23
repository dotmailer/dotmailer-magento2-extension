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
     * SaveStatusSmsAutomation constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory     $automationFactory
     * @param \Dotdigitalgroup\Email\Model\OrderFactory          $emailOrderFactory
     * @param \Magento\Framework\Registry                        $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManagerInterface
     * @param \Magento\Store\Model\App\EmulationFactory          $emulationFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Store\Model\App\EmulationFactory $emulationFactory
    ) {
        $this->_automationFactory = $automationFactory;
        $this->_emailOrderFactory = $emailOrderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManagerInterface;
        $this->_registry = $registry;
        $this->_emulationFactory = $emulationFactory;
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
                        try {
                            $programId = $configMap['automation'];
                            $automation = $this->_automationFactory->create();
                            $automation->setEmail($customerEmail)
                                ->setAutomationType('order_automation_'
                                    . $status)
                                ->setEnrolmentStatus(
                                    \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                                ->setTypeId($order->getId())
                                ->setWebsiteId($websiteId)
                                ->setStoreName($storeName)
                                ->setProgramId($programId);
                            //@codingStandardsIgnoreStart
                            $automation->save();
                            //@codingStandardsIgnoreEnd
                        } catch (\Exception $e) {
                            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                        }
                    }
                }
            }
            //admin oder when editing the first one is canceled
            $this->_registry->unregister('sales_order_status_before');
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $this;
    }
}
