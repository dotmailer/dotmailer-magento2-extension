<?php

namespace Dotdigitalgroup\Email\Test\Integration\Sync;

if (!class_exists('\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory')) {
    require __DIR__ . '/../_files/product_extension_interface_hacktory.php';
}

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\Order;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Payment;
use Magento\TestFramework\ObjectManager;

/**
 * Class OrderSyncTest
 * magentoAppArea cron
 */
class OrderSyncTest extends \Magento\TestFramework\TestCase\AbstractController
{
    use MocksApiResponses;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    public $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    public $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\AccountFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    public $account;

    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    public $orderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact|\PHPUnit_Framework_MockObject_MockObject
     */
    public $contactResource;

    /**
     * @var \Magento\Framework\App\ResourceConnection | \PHPUnit_Framework_MockObject_MockObject
     */
    public $resource;

    /**
     * @var \Magento\Sales\Model\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    public $salesOrderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\OrderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    public $connectorOrderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\Order
     */
    public $orderSync;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order \PHPUnit_Framework_MockObject_MockObject
     */
    public $orderResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory |
     * \PHPUnit_Framework_MockObject_MockObject
     */
    public $contactCollectionFactory;

    /**
     * @return void
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
        ]);
        ObjectManager::getInstance()
            ->create(\Dotdigitalgroup\Email\Model\ResourceModel\Order::class)
            ->save($emailOrder);

        $result = $this->orderSync->sync();

        $this->assertNotEmpty($result['syncedOrders'], 'Failed, no orders synced.');
        $this->assertEquals(1, $result['syncedOrders']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     */
    public function testCanSyncUnprocessedOrders()
    {
        /** @var \Magento\Sales\Model\Order $latestOrder */
        $orderCollection = ObjectManager::getInstance()
            ->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $latestOrder = $orderCollection->getFirstItem();

        /** @var \Dotdigitalgroup\Email\Model\Order $order */
        $order = ObjectManager::getInstance()->create(\Dotdigitalgroup\Email\Model\Order::class);
        $order->setData([
            'order_id' => $latestOrder->getId(),
            'order_status' => $latestOrder->getStatus(),
            'quote_id' => $latestOrder->getQuoteId(),
            'store_id' => $latestOrder->getStoreId(),
            'processed' => '0',
        ]);
        ObjectManager::getInstance()
            ->create(\Dotdigitalgroup\Email\Model\ResourceModel\Order::class)
            ->save($order);

        $result = $this->orderSync->sync();

        $this->assertEquals(1, $result['syncedOrders']);
    }

    /**
     * @return void
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

    /**
     * @return void
     */
    private function createModifiedEmailOrder()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $order = $objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $order = $order->getFirstItem();
        $emailOrder = $objectManager->create(\Dotdigitalgroup\Email\Model\Order::class);
        $emailOrder->setOrderId($order->getId());
        $emailOrder->setOrderStatus($order->getStatus());
        $emailOrder->setQuoteId($order->getQuoteId());
        $emailOrder->setStoreId($order->getStoreId());
        $emailOrder->setEmailImported('1');
        $emailOrder->setModified('1');
        $emailOrder->save();
    }
}
