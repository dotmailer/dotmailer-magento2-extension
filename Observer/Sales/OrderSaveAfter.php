<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Observer\Sales;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\OrderFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\SubscriptionDataFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Subscriber as DotdigitalSubscriber;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\App\EmulationFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Trigger Order automation based on order state.
 */
class OrderSaveAfter implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Automation
     */
    private $automationResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order
     */
    private $orderResource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EmulationFactory
     */
    private $emulationFactory;

    /**
     * @var OrderFactory
     */
    private $emailOrderFactory;

    /**
     * @var AutomationFactory
     */
    private $automationFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var SerializerInterface
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
     * @var CollectionFactory
     */
    private $emailAutomationFactory;

    /**
     * @var SubscriptionDataFactory
     */
    private $subscriptionDataFactory;

    /**
     * @var AutomationPublisher
     */
    private $automationPublisher;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param Logger $logger
     * @param AutomationFactory $automationFactory
     * @param Automation $automationResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource
     * @param OrderFactory $emailOrderFactory
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManagerInterface
     * @param EmulationFactory $emulationFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param Data $data
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory
     * @param CollectionFactory $emailAutomationFactory
     * @param SubscriptionDataFactory $subscriptionDataFactory
     * @param AutomationPublisher $automationPublisher
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Logger $logger,
        AutomationFactory $automationFactory,
        Automation $automationResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource,
        OrderFactory $emailOrderFactory,
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface,
        EmulationFactory $emulationFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        Data $data,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory,
        CollectionFactory $emailAutomationFactory,
        SubscriptionDataFactory $subscriptionDataFactory,
        AutomationPublisher $automationPublisher,
        PublisherInterface $publisher
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->orderResource = $orderResource;
        $this->automationResource = $automationResource;
        $this->automationFactory = $automationFactory;
        $this->emailOrderFactory = $emailOrderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManagerInterface;
        $this->emulationFactory = $emulationFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $data;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->emailAutomationFactory = $emailAutomationFactory;
        $this->subscriptionDataFactory = $subscriptionDataFactory;
        $this->automationPublisher = $automationPublisher;
        $this->publisher = $publisher;
    }

    /**
     * Save/reset the order as transactional data.
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
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
            $appEmulation->startEnvironmentEmulation($storeId);
            $emailOrder = $this->emailOrderFactory->create()
                ->loadOrCreateOrder($order->getEntityId(), $order->getQuoteId());
            //reimport email order
            $emailOrder->setUpdatedAt($order->getUpdatedAt())
                ->setCreatedAt($order->getUpdatedAt())
                ->setStoreId($storeId)
                ->setProcessed(0)
                ->setOrderStatus($status);

            // set back the current store
            $appEmulation->stopEnvironmentEmulation();
            $this->orderResource->save($emailOrder);

            if (!$this->helper->isEnabled($websiteId)) {
                return $this;
            }

            $this->statusCheckAutomationEnrolment($order, $status, $customerEmail, $websiteId, $storeId, $storeName);

            //Reset contact if found
            $this->resetContactByEmailAndWebsiteId($customerEmail, $websiteId);

            //If customer's first order
            if ($order->getCustomerId()) {
                $orders = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('customer_id', $order->getCustomerId());
                if ($orders->getSize()==1) {
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
        } catch (\Exception $e) {
            $this->logger->error('Error in OrderSaveAfter observer', [(string) $e]);
        }

        return $this;
    }

    /**
     * Reset contact based on email and website_id.
     *
     * Reset any customers, guest or subscribers after saving an order.
     *
     * @param string $email
     * @param int $websiteId
     *
     * @throws AlreadyExistsException
     */
    private function resetContactByEmailAndWebsiteId($email, $websiteId)
    {
        $contact = $this->contactCollectionFactory->create()
            ->loadByCustomerEmail($email, $websiteId);

        if (!$contact) {
            return;
        }

        $contact->setEmailImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
        $this->contactResource->save($contact);

        if (! $contact->getCustomerId() && $contact->getIsSubscriber()) {
            // the queue will do the sync so mark as imported now
            $contact->setSubscriberImported(Contact::EMAIL_CONTACT_IMPORTED);
            $this->contactResource->save($contact);

            $subscriptionData = $this->subscriptionDataFactory->create();
            $subscriptionData->setId($contact->getId());
            $subscriptionData->setEmail($contact->getEmail());
            $subscriptionData->setWebsiteId($contact->getWebsiteId());
            $subscriptionData->setType('subscribe');
            $this->publisher->publish(DotdigitalSubscriber::TOPIC_NEWSLETTER_SUBSCRIPTION, $subscriptionData);
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

                    $this->automationPublisher->publish($automation);
                }
            } catch (Exception $e) {
                $this->logger->debug((string)$e, []);
            }
        } else {
            $this->logger->info('automation type : ' . $data['automationType'] . ' program id not found');
        }
    }

    /**
     * Enrol into automation if order status matches selected statuses.
     *
     * @param Order $order
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
            Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (null === $orderStatusAutomations) {
            return;
        }

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
        } catch (InvalidArgumentException $e) {
            $this->logger->debug((string)$e, []);
            return;
        }
    }
}
