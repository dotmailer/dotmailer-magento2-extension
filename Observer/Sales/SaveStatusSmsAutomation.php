<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

class SaveStatusSmsAutomation implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Magento\Store\Model\App\EmulationFactory
     */
    public $emulationFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    public $emailOrderFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    public $automationFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    public $orderCollectionFactory;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

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
        $this->automationFactory      = $automationFactory;
        $this->emailOrderFactory      = $emailOrderFactory;
        $this->scopeConfig            = $scopeConfig;
        $this->storeManager           = $storeManagerInterface;
        $this->registry               = $registry;
        $this->emulationFactory       = $emulationFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper                 = $data;
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
            $status         = $order->getStatus();
            $storeId        = $order->getStoreId();
            $customerEmail  = $order->getCustomerEmail();
            $store      = $this->storeManager->getStore($storeId);
            $storeName  = $store->getName();
            $websiteId  = $store->getWebsiteId();
            // start app emulation
            $appEmulation = $this->emulationFactory->create();
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
            $emailOrder = $this->emailOrderFactory->create()
                ->loadByOrderId($order->getEntityId(), $order->getQuoteId());
            //reimport email order
            $emailOrder->setUpdatedAt($order->getUpdatedAt())
                ->setCreatedAt($order->getUpdatedAt())
                ->setStoreId($storeId)
                ->setOrderStatus($status);

            if ($emailOrder->getEmailImported() != \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED) {
                $emailOrder->setEmailImported(null);
            }

            $isEnabled = $this->helper->isStoreEnabled($storeId);

            //api not enabled, stop emulation and exit
            if (! $isEnabled) {
                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                return $this;
            }

            // check for order status change
            $statusBefore = $this->registry->registry('sales_order_status_before');
            if ($status != $statusBefore) {
                //If order status has changed and order is already imported then set modified to 1
                if ($emailOrder->getEmailImported() == \Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED) {
                    $emailOrder->setModified(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_IMPORTED);
                }
            }
            // set back the current store
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            $emailOrder->save();

            //@codingStandardsIgnoreStart
            //Status check automation enrolment
            $configStatusAutomationMap = unserialize(
                $this->scopeConfig->getValue(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStore()
                )
            );
            //@codingStandardsIgnoreEnd
            if (! empty($configStatusAutomationMap)) {
                foreach ($configStatusAutomationMap as $configMap) {
                    if ($configMap['status'] == $status) {
                        //send to automation queue
                        $this->doAutomationEnrolment(
                            [
                                'programId' => $configMap['automation'],
                                'automationType' => 'order_automation_' . $status,
                                'email' => $customerEmail,
                                'order_id' => $order->getIncrementId(),
                                'website_id' => $websiteId,
                                'store_name' => $storeName
                            ]
                        );
                    }
                }
            }
            //If customer's first order, also order state is new
            if ($order->getCustomerId() && $order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
                $orders = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('customer_id', $order->getCustomerId());
                if ($orders->getSize() == 1) {
                    $automationTypeNewOrder
                        = \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER;
                    $programIdNewOrder = $this->helper->getAutomationIdByType(
                        'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_FIRST_ORDER',
                        $order->getStoreId()
                    );
                    if ($programIdNewOrder) {
                        //send to automation queue
                        $this->doAutomationEnrolment(
                            [
                                'programId' => $programIdNewOrder,
                                'automationType' => $automationTypeNewOrder,
                                'email' => $customerEmail,
                                'order_id' => $order->getIncrementId(),
                                'website_id' => $websiteId,
                                'store_name' => $storeName
                            ]
                        );
                    }
                }
            }
            //admin oder when editing the first one is canceled
            $this->registry->unregister('sales_order_status_before');
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Save enrolment to queue for cron automation enrolment.
     *
     * @param $data
     */
    protected function doAutomationEnrolment($data)
    {
        //the program is not mapped
        if ($data['programId']) {
            try {
                $this->automationFactory->create()
                    ->setEmail($data['email'])
                    ->setAutomationType($data['automationType'])
                    ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
                    ->setTypeId($data['order_id'])
                    ->setWebsiteId($data['website_id'])
                    ->setStoreName($data['store_name'])
                    ->setProgramId($data['programId'])
                    ->save();
            } catch (\Exception $e) {
                $this->helper->debug((string)$e, []);
            }

        } else {
            $this->helper->log(
                'automation type : ' . $data['automationType'] . ' program id not found'
            );
        }
    }
}
