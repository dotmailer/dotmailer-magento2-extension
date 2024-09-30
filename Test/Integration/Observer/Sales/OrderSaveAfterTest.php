<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Integration\Observer\Sales;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection as AutomationCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory as AutomationCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Dotdigitalgroup\Email\Model\Subscriber as DotdigitalSubscriber;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\MysqlMq\Model\ResourceModel\MessageCollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Collection as SalesOrderCollection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class OrderSaveAfterTest extends TestCase
{
    use MocksApiResponses;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var AutomationCollectionFactory
     */
    private $automationCollectionFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderFactory
     */
    private $salesOrderFactory;

    /**
     * @var MessageCollectionFactory
     */
    private $queueMessageCollectionFactory;

    /**
     * @return void
     */
    public function setup() :void
    {
        parent::setUp();

        $this->objectManager = ObjectManager::getInstance();
        $this->automationCollectionFactory = $this->objectManager->create(AutomationCollectionFactory::class);
        $this->contactResource = $this->objectManager->create(ContactResource::class);
        $this->contactFactory = $this->objectManager->create(ContactFactory::class);
        $this->contactCollectionFactory = $this->objectManager->create(ContactCollectionFactory::class);
        $this->orderCollectionFactory = $this->objectManager->create(OrderCollectionFactory::class);
        $this->salesOrderFactory = $this->objectManager->create(OrderFactory::class);
        $this->queueMessageCollectionFactory = $this->objectManager->create(MessageCollectionFactory::class);
    }

    /**
     * This test concerns the bit of the observer that runs before the isEnabled check.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRowIsAddedToEmailOrder()
    {
        $salesOrder = $this->salesOrderFactory->create()
            ->loadByIncrementId('100000001');

        $this->assertNotNull($salesOrder->getId(), 'Order should exist');

        $dotdigitalOrder = $this->orderCollectionFactory->create()
            ->addFieldToFilter('order_id', $salesOrder->getId())
            ->getFirstItem();

        $this->assertEquals($salesOrder->getStoreId(), $dotdigitalOrder->getStoreId());
        $this->assertEquals($salesOrder->getStatus(), $dotdigitalOrder->getOrderStatus());
        $this->assertEquals(0, $dotdigitalOrder->getProcessed());
        $this->assertNull($dotdigitalOrder->getLastImportedAt(), 'Last imported at should default to null');
    }

    /**
     * Test that an imported contact is reset when they place an order.
     *
     * We must create customer and order inside the test.
     * If we use data fixtures, the observer will be triggered straight away (before setUp)
     * and will not complete, because the isEnabled check won't pass.
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testAlreadyImportedCustomerIsResetIfStoreEnabled()
    {
        $this->setApiConfigFlags();

        $customer = $this->createCustomer();

        // mark the contact as imported
        $contact = $this->contactCollectionFactory->create()
            ->loadByCustomerEmail($customer->getEmail(), $customer->getWebsiteId());
        $contact->setEmailImported(1);
        $this->contactResource->save($contact);

        // create customer order
        $this->createOrder(true);

        // reload the contact to confirm
        $contact = $this->contactCollectionFactory->create()
            ->loadByCustomerEmail($customer->getEmail(), $customer->getWebsiteId());

        $this->assertEquals(
            Contact::EMAIL_CONTACT_NOT_IMPORTED,
            $contact->getEmailImported(),
            'Contact should be reset to not imported'
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testSubscribedGuestIsResyncedViaQueue()
    {
        $this->setApiConfigFlags();

        $contact = $this->contactFactory->create()
            ->loadByEmailOrCreateWithScope('customer@example.com', 1, 1);
        $contact->setIsGuest(true);
        $contact->setIsSubscriber(true);
        $this->contactResource->save($contact);

        // create guest order
        $this->createOrder(false);

        // reload the contact to confirm
        $contact = $this->contactCollectionFactory->create()
            ->loadByCustomerEmail('customer@example.com', 1);

        $this->assertEquals(
            Contact::EMAIL_CONTACT_IMPORTED,
            $contact->getSubscriberImported(),
            'Subscriber should now be marked as imported'
        );

        // check queue
        $queueMessage = $this->queueMessageCollectionFactory->create()
            ->addFieldToFilter('topic_name', DotdigitalSubscriber::TOPIC_NEWSLETTER_SUBSCRIPTION)
            ->getLastItem();

        $this->assertNotNull($queueMessage->getId(), 'Queue message should exist');
        $this->assertEquals(
            sprintf(
                '{"id":"%s","email":"customer@example.com","website_id":1,"type":"subscribe"}',
                $contact->getId()
            ),
            $queueMessage->getBody()
        );
    }

    /**
     * Test that an order status automation is created.
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testMatchingOrderStatusTriggersProgramEnrolment()
    {
        $this->setApiConfigFlags();
        $this->getMutableScopeConfig()->setValue(
            Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_ORDER_STATUS,
            '{"1":{"status":"processing","automation":"12345"}}',
            ScopeInterface::SCOPE_STORE
        );

        $customer = $this->createCustomer();
        $this->createOrder(true);

        $automation = $this->automationCollectionFactory->create()
            ->addFieldToFilter('email', $customer->getEmail())
            ->addFieldToFilter('automation_type', 'order_automation_processing')
            ->getLastItem();

        $this->assertNotNull($automation->getId(), 'Automation should exist');
    }

    /**
     * Test that the first customer order automation is created.
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testFirstCustomerOrderTriggersProgramEnrolment()
    {
        $this->setApiConfigFlags();
        $this->getMutableScopeConfig()->setValue(
            Config::XML_PATH_CONNECTOR_AUTOMATION_STUDIO_FIRST_ORDER,
            12345,
            ScopeInterface::SCOPE_STORE
        );

        $customer = $this->createCustomer();
        $this->createOrder(true);

        $automation = $this->automationCollectionFactory->create()
            ->addFieldToFilter('email', $customer->getEmail())
            ->addFieldToFilter('automation_type', AutomationTypeHandler::AUTOMATION_TYPE_CUSTOMER_FIRST_ORDER)
            ->getLastItem();

        $this->assertNotNull($automation->getId(), 'Automation should exist');
    }

    /**
     * See Magento/Customer/_files/customer.php
     *
     * @return Customer
     * @throws \Exception
     */
    private function createCustomer()
    {
        $customer = $this->objectManager->create(Customer::class);
        /** @var CustomerRegistry $customerRegistry */
        $customerRegistry = $this->objectManager->get(CustomerRegistry::class);
        /** @var Customer $customer */
        $customer->setWebsiteId(1)
            ->setId(1)
            ->setEmail('customer@example.com')
            ->setPassword('password')
            ->setGroupId(1)
            ->setStoreId(1)
            ->setIsActive(1)
            ->setPrefix('Mr.')
            ->setFirstname('John')
            ->setMiddlename('A')
            ->setLastname('Smith')
            ->setSuffix('Esq.')
            ->setDefaultBilling(1)
            ->setDefaultShipping(1)
            ->setTaxvat('12')
            ->setGender(0);

        $customer->isObjectNew(true);
        $customer->save();
        $customerRegistry->remove($customer->getId());
        /** @var \Magento\JwtUserToken\Api\RevokedRepositoryInterface $revokedRepo */
        $revokedRepo = $this->objectManager->get(\Magento\JwtUserToken\Api\RevokedRepositoryInterface::class);
        $revokedRepo->saveRevoked(
            new \Magento\JwtUserToken\Api\Data\Revoked(
                \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER,
                (int) $customer->getId(),
                time() - 3600 * 24
            )
        );

        return $customer;
    }

    /**
     * See Magento/Sales/_files/order.php
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createOrder(bool $isCustomer = false)
    {
        $addressData = [
            'region' => 'CA',
            'region_id' => '12',
            'postcode' => '11111',
            'company' => 'Test Company',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'street' => 'street',
            'city' => 'Los Angeles',
            'email' => 'customer@example.com',
            'telephone' => '11111111',
            'country_id' => 'US'
        ];

        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->create(ProductRepositoryInterface::class);
        $product = $productRepository->get('simple');
        $billingAddress = $objectManager->create(OrderAddress::class, ['data' => $addressData]);
        $billingAddress->setAddressType('billing');

        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');

        /** @var Payment $payment */
        $payment = $objectManager->create(Payment::class);
        $payment->setMethod('checkmo')
            ->setAdditionalInformation('last_trans_id', '11122')
            ->setAdditionalInformation(
                'metadata',
                [
                    'type' => 'free',
                    'fraudulent' => false,
                ]
            );

        /** @var OrderItem $orderItem */
        $orderItem = $objectManager->create(OrderItem::class);
        $orderItem->setProductId($product->getId())
            ->setQtyOrdered(2)
            ->setBasePrice($product->getPrice())
            ->setPrice($product->getPrice())
            ->setRowTotal($product->getPrice())
            ->setProductType('simple')
            ->setName($product->getName())
            ->setSku($product->getSku());

        /** @var Order $order */
        $order = $objectManager->create(Order::class);
        $order->setIncrementId('100000001')
            ->setState(Order::STATE_PROCESSING)
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
            ->setSubtotal(100)
            ->setGrandTotal(100)
            ->setBaseSubtotal(100)
            ->setBaseGrandTotal(100)
            ->setOrderCurrencyCode('USD')
            ->setBaseCurrencyCode('USD')
            ->setCustomerIsGuest($isCustomer ? false : true)
            ->setCustomerEmail('customer@example.com')
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setStoreId($objectManager->get(StoreManagerInterface::class)->getStore()->getId())
            ->addItem($orderItem)
            ->setPayment($payment);

        if ($isCustomer) {
            $order->setCustomerId(1);
        }

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $objectManager->create(OrderRepositoryInterface::class);
        $orderRepository->save($order);
    }

    /**
     * Clear down the test data.
     *
     * Again, all this must be done because we are not using fixtures in the conventional way
     * (see comments above).
     *
     * @return void
     * @throws \Exception
     */
    public static function tearDownAfterClass(): void
    {
        $registry = ObjectManager::getInstance()->get(\Magento\Framework\Registry::class);
        $originalValue = $registry->registry('isSecureArea');
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        $salesOrderCollection = ObjectManager::getInstance()->get(SalesOrderCollection::class);
        foreach ($salesOrderCollection->getAllIds() as $id) {
            $order = ObjectManager::getInstance()->get(Order::class);
            $orderResource = ObjectManager::getInstance()->get(OrderResource::class);
            $orderResource->load($order, $id);
            $orderResource->delete($order);
        }

        $customerCollection = ObjectManager::getInstance()->get(CustomerCollection::class);
        foreach ($customerCollection->getAllIds() as $id) {
            $customer = ObjectManager::getInstance()->get(Customer::class);
            $customerResource = ObjectManager::getInstance()->get(CustomerResource::class);
            $customerResource->load($customer, $id);
            $customerResource->delete($customer);
        }

        $automationCollection = ObjectManager::getInstance()->get(AutomationCollection::class);
        foreach ($automationCollection->getAllIds() as $id) {
            $automation = ObjectManager::getInstance()->get(Automation::class);
            $automationResource = ObjectManager::getInstance()->get(AutomationResource::class);
            $automationResource->load($automation, $id);
            $automationResource->delete($automation);
        }

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', $originalValue);
    }
}
