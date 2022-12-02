<?php

namespace Dotdigitalgroup\Email\Observer\Sales;

use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\StatusInterface;

/**
 * Trigger Order automation based on order state.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Automation
     */
    private $automationResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order
     */
    private $orderResource;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Store\Model\App\EmulationFactory
     */
    private $emulationFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    private $emailOrderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\AutomationFactory
     */
    private $automationFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory
     */
    private $emailAutomationFactory;

    /**
     * SaveStatusSmsAutomation constructor.
     * @param \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory
     * @param Automation $automationResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource
     * @param \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory
     * @param \Magento\Framework\Registry $registry
     * @param Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Store\Model\App\EmulationFactory $emulationFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $emailAutomationFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation $automationResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource,
        \Dotdigitalgroup\Email\Model\OrderFactory $emailOrderFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Store\Model\App\EmulationFactory $emulationFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $emailAutomationFactory
    ) {
        $this->serializer = $serializer;
        $this->orderResource = $orderResource;
        $this->automationResource = $automationResource;
        $this->automationFactory      = $automationFactory;
        $this->emailOrderFactory      = $emailOrderFactory;
        $this->scopeConfig            = $scopeConfig;
        $this->storeManager           = $storeManagerInterface;
        $this->registry               = $registry;
        $this->emulationFactory       = $emulationFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper                 = $data;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->emailAutomationFactory = $emailAutomationFactory;
    }

    /**
     * Save/reset the order as transactional data.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $status         = $order->getStatus();
        $storeId        = $order->getStoreId();
        $customerEmail  = $order->getCustomerEmail();
        $store      = $this->storeManager->getStore($storeId);
        $storeName  = $store->getName();
        $websiteId  = $store->getWebsiteId();
        if (empty($storeId)) {
            $storeId = $store->getId();
        }
        // start app emulation
        $appEmulation = $this->emulationFactory->create();
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
        $emailOrder = $this->emailOrderFactory->create()
            ->loadByOrderId($order->getEntityId(), $order->getQuoteId());
        //reimport email order
        $emailOrder->setUpdatedAt($order->getUpdatedAt())
            ->setCreatedAt($order->getUpdatedAt())
            ->setStoreId($storeId)
            ->setProcessed(0)
            ->setOrderStatus($status);

        $isEnabled = $this->helper->isStoreEnabled($storeId);

        //api not enabled, stop emulation and exit
        if (! $isEnabled) {
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            return $this;
        }

        // set back the current store
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        $this->orderResource->save($emailOrder);

        $this->statusCheckAutomationEnrolment($order, $status, $customerEmail, $websiteId, $storeId, $storeName);

        //Reset contact if found
        $contactCollection = $this->contactCollectionFactory->create();
        $contact = $contactCollection->loadByCustomerEmail($customerEmail, $websiteId);
        if ($contact) {
            $this->resetContact($contact);
        }

        //If customer's first order
        if ($order->getCustomerId()) {
            $orders = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $order->getCustomerId());
            if ($orders->getSize() == 1) {
                $automationTypeNewOrder = AutomationTypeHandler::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER;
                $programIdNewOrder = $this->helper->getAutomationIdByType(
                    'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_FIRST_ORDER',
                    $storeId
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
                            'store_id' => $storeId,
                            'store_name' => $storeName
                        ]
                    );
                }
            }
        }
        //admin oder when editing the first one is canceled
        $this->registry->unregister('sales_order_status_before');

        return $this;
    }

    /**
     * Reset contact based on type and status
     *
     * @param \Dotdigitalgroup\Email\Model\Contact $contact
     */
    private function resetContact($contact)
    {
        if ($contact->getCustomerId() && $contact->getEmailImported()) {
            $contact->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED);
            $this->contactResource->save($contact);
        } elseif (! $contact->getCustomerId() && $contact->getIsSubscriber() && $contact->getSubscriberImported()) {
            $contact->setSubscriberImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED);
            $this->contactResource->save($contact);
        }
    }

    /**
     * Save enrolment to queue for cron automation enrolment.
     *
     * @param array $data
     */
    private function doAutomationEnrolment($data)
    {
        //the program is not mapped
        if ($data['programId']) {
            try {
                $typeId = $data['order_id'];
                $automationTypeId = $data['automationType'];
                $exists = $this->emailAutomationFactory->create()
                    ->addFieldToFilter('type_id', $typeId)
                    ->addFieldToFilter('automation_type', $automationTypeId)
                    ->setPageSize(1);

                //automation type, and type should be unique
                if (! $exists->getSize()) {
                    $automation = $this->automationFactory->create()
                        ->setEmail($data['email'])
                        ->setAutomationType($data['automationType'])
                        ->setEnrolmentStatus(StatusInterface::PENDING)
                        ->setTypeId($data['order_id'])
                        ->setWebsiteId($data['website_id'])
                        ->setStoreId($data['store_id'])
                        ->setStoreName($data['store_name'])
                        ->setProgramId($data['programId']);
                    $this->automationResource->save($automation);
                }
            } catch (\Exception $e) {
                $this->helper->debug((string)$e, []);
            }
        } else {
            $this->helper->log('automation type : ' . $data['automationType'] . ' program id not found');
        }
    }

    /**
     * Enrol into automation if order status matches selected statuses.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $status
     * @param string $customerEmail
     * @param int $websiteId
     * @param int $storeId
     * @param string $storeName
     *
     * @return void
     */
    private function statusCheckAutomationEnrolment($order, $status, $customerEmail, $websiteId, $storeId, $storeName)
    {
        $orderStatusAutomations = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        try {
            $configStatusAutomationMap = $this->serializer->unserialize($orderStatusAutomations);
            if (!is_array($configStatusAutomationMap)) {
                return;
            }
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
                            'store_id' => $storeId,
                            'store_name' => $storeName
                        ]
                    );
                }
            }
        } catch (\InvalidArgumentException $e) {
            return;
        }
    }
}
