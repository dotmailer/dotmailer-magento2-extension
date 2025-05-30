<?php

namespace Dotdigitalgroup\Email\Test\Integration\Sync;

if (!class_exists('\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory')) {
    require __DIR__ . '/../_files/product_extension_interface_hacktory.php';
}

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Sync\Order;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Payment;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

class OrderSyncTest extends \Magento\TestFramework\TestCase\AbstractController
{
    use MocksApiResponses;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\AccountFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $account;

    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResource;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resource;

    /**
     * @var \Magento\Sales\Model\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $salesOrderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectorOrderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\Order
     */
    private $orderSync;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactCollectionFactory;

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function setUp() :void
    {
        parent::setUp();

        $this->setApiConfigFlags([
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED => 1,
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS => implode(',', [
                \Magento\Sales\Model\Order::STATE_PROCESSING,
                \Magento\Sales\Model\Order::STATE_COMPLETE,
            ])
        ]);
        $this->instantiateDataHelper();

        $this->orderSync = ObjectManager::getInstance()->create(Order::class);
    }

    /**
     * Test that orders are not synced without a contact id.
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     */
    public function testOrderNotSyncedForContactWithNoContactId()
    {
        /** @var Collection $quoteCollection */
        $quoteCollection = ObjectManager::getInstance()->create(Collection::class);
        $latestQuote = $quoteCollection->getLastItem();

        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = ObjectManager::getInstance()
            ->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $order = $orderCollection->getFirstItem();
        $order->setQuoteId($latestQuote->getId());
        $order->save();

        /** @var \Dotdigitalgroup\Email\Model\Order $order */
        $emailOrder = ObjectManager::getInstance()->create(\Dotdigitalgroup\Email\Model\Order::class);
        $emailOrder->setData([
            'order_id' => $order->getId(),
            'order_status' => $order->getStatus(),
            'quote_id' => $order->getQuoteId(),
            'store_id' => $order->getStoreId(),
            'processed' => '0',
        ]);
        ObjectManager::getInstance()
            ->create(\Dotdigitalgroup\Email\Model\ResourceModel\Order::class)
            ->save($emailOrder);

        $result = $this->orderSync->sync();

        $this->assertEmpty($result['syncedOrders'], 'Failed, should not have synced order.');
        $this->assertEquals(0, $result['syncedOrders']);
    }

    /**
     * Sync orders and find guest.
     *
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     */
    public function testOrderSync()
    {
        $order = Bootstrap::getObjectManager()->get(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId('100000001');

        $emailContact = ObjectManager::getInstance()->create(Contact::class);
        $emailContact->setData([
            'contact_id' => '12345',
            'email' => $order->getCustomerEmail(),
            'website_id' => $order->getStore()->getWebsiteId(),
            'store_id' => $order->getStoreId()
        ]);
        ObjectManager::getInstance()
            ->create(\Dotdigitalgroup\Email\Model\ResourceModel\Contact::class)
            ->save($emailContact);

        $result = $this->orderSync->sync();

        $this->assertNotEmpty($result['syncedOrders'], 'Failed, no orders synced.');
        $this->assertEquals(1, $result['syncedOrders']);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function createNewEmailOrder()
    {
        $objectManager = ObjectManager::getInstance();

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $objectManager->create(OrderRepositoryInterface::class);
        /** @var array $orderData */
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
        /** @var $order \Magento\Sales\Model\Order */
        $order = $objectManager->create(
            \Magento\Sales\Model\Order::class
        );

        // Reset addresses
        /** @var OrderAddress $billingAddress */
        $billingAddress = $objectManager->create(OrderAddress::class, [
            'data' => [
                'region' => 'CA',
                'region_id' => '12',
                'postcode' => '11111',
                'lastname' => 'lastname',
                'firstname' => 'firstname',
                'street' => 'street',
                'city' => 'Los Angeles',
                'email' => 'admin@example.com',
                'telephone' => '11111111',
                'country_id' => 'US',
            ],
        ]);
        $billingAddress->setAddressType('billing');

        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');

        $order->setData([
                'increment_id' => '545365',
                'state' => \Magento\Sales\Model\Order::STATE_PROCESSING,
                'status' => 'processing',
                'grand_total' => 130.00,
                'base_grand_total' => 130.00,
                'subtotal' => 130.00,
                'total_paid' => 130.00,
                'store_id' => 0,
                'website_id' => 0,
                'payment' => $payment,
            ])
            ->setCustomerIsGuest(true)
            ->setCustomerEmail('customer@example.com')
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress);

        $orderRepository->save($order);
    }
}
