<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

use Magento\TestFramework\ObjectManager;

/**
 * Class OrderSyncTest
 * @package Dotdigitalgroup\Email\Controller\Customer
 * @magentoDBIsolation enabled
 * magentoAppArea cron
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderSyncTest extends \Magento\TestFramework\TestCase\AbstractController
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    public $helper;

    /**
     * @var \Magento\Store\Model\StoreManager|\PHPUnit_Framework_MockObject_MockObject
     */
    public $storeManager;

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
    public $_orderSync;

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
    public function setUp()
    {
        $this->importerFactory = $this->getMock('Dotdigitalgroup\Email\Model\ImporterFactory', [], [], '', false);
        $this->account = ObjectManager::getInstance()->get('Dotdigitalgroup\Email\Model\Connector\AccountFactory');
        $this->orderFactory = $this->getMock('Dotdigitalgroup\Email\Model\OrderFactory', [], [], '', false);
        $this->connectorOrderFactory = $this->getMock(
            'Dotdigitalgroup\Email\Model\Connector\OrderFactory',
            [],
            [],
            '',
            false
        );
        $this->contactResource = ObjectManager::getInstance()->get(
            \Dotdigitalgroup\Email\Model\ResourceModel\Contact::class
        );
        $this->orderResource = ObjectManager::getInstance()->get(
            \Dotdigitalgroup\Email\Model\ResourceModel\Order::class
        );
        $this->helper = $this->getMock(\Dotdigitalgroup\Email\Helper\Data::class, [], [], '', false);
        $this->salesOrderFactory = $this->getMock('Magento\Sales\Model\OrderFactory', [], [], '', false);
        $this->storeManager = ObjectManager::getInstance()->get(\Magento\Store\Model\StoreManagerInterface::class);
        $this->contactCollectionFactory = ObjectManager::getInstance()->get(
            \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory::class
        );
        $this->_orderSync = new \Dotdigitalgroup\Email\Model\Sync\Order(
            $this->importerFactory,
            $this->orderFactory,
            $this->account,
            $this->connectorOrderFactory,
            $this->contactResource,
            $this->contactCollectionFactory,
            $this->orderResource,
            $this->helper,
            $this->salesOrderFactory,
            $this->storeManager
        );
    }

    /**
     * Sync orders and find guest.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     *
     * @return null
     */
    public function testOrderSyncCanFindGuests()
    {
        $this->createNewEmailOrder();
        $this->prepareSync();

        $this->helper->expects($this->atLeastOnce())->method('getApiUsername')
            ->willReturn('apiuser-dummy@apiconnector.com');
        $this->helper->expects($this->atLeastOnce())->method('getApiPassword')
            ->willReturn('dummy123');
        $this->helper->expects($this->never())->method('debug');
        $this->helper->expects($this->atLeastOnce())->method('log');

        $this->_orderSync->sync();

        $this->assertNotEmpty($this->_orderSync->guests, 'Failed no guests found to sync.');
        $this->assertEquals(
            '1',
            $this->_orderSync->countOrders,
            'Number of orders synced not matching.'
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     *
     * @return null
     */
    public function testGuestsOrderNotCreatingDuplicatesContact()
    {
        $this->createNewEmailOrder();
        $this->prepareSync();

        $this->_orderSync->sync();

        $this->assertEquals('1', count($this->_orderSync->guests));
        $this->assertEquals('1', $this->_orderSync->countOrders);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     *
     * @return null
     */
    public function testGuestFoundMarkedIsGuest()
    {
        $this->createNewEmailOrder();
        $this->prepareSync();

        $this->_orderSync->sync();
        $guests = $this->_orderSync->guests;
        $this->assertArrayHasKey('is_guest', $guests[key($guests)]);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     *
     * @return null
     */
    public function testCanSyncModifiedOrders()
    {
        $this->createModifiedEmailOrder();

        $this->prepareSync(true);

        $this->helper->expects($this->atLeastOnce())->method('getApiUsername')
            ->willReturn('apiuser-dummy@apiconnector.com');
        $this->_orderSync->sync();

        $this->assertEquals('1', count($this->_orderSync->guests));
        $this->assertEquals('1', $this->_orderSync->countOrders);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     * @param int $withGuests
     */
    protected function prepareSync($withGuests = 0)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

        $website = $objectManager->create(\Magento\Store\Model\Website::class);
        $website->setData(
            ['code' => 'test', 'name' => 'Test Website', 'default_group_id' => '1', 'is_default' => '0']
        );
        $website->save();
        /** @var $store \Magento\Store\Model\Store */
        $store = $objectManager->create(\Magento\Store\Model\Store::class);
        $store->setData(
            [
            'code' => 'test',
            'website_id' => $website->getId(),
            'group_id' => '1',
            'name' => 'Test Store',
            'sort_order' => '0',
            'is_active' => '1'
            ]
        );
        $store->save();

        $statuses = [
            'completed', 'proccessing', 'pending'
        ];

        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('getWebsites')->willReturn([$website]);
        $this->helper->method('getApiUsername')->willReturn('apiuser-dummy@apiconnector.com');
        $this->helper->method('getApiPassword')->willReturn('dummypass');
        $this->helper->method('getWebsiteConfig')->willReturn('2');
        $this->helper->method('getConfigSelectedStatus')->willReturn($statuses);

        $this->importerFactory->method('create')->willReturn(
            $this->getMock(\Dotdigitalgroup\Email\Model\Importer::class, ['registerQueue'], [], '', false)
        );
        $this->importerFactory->method('registerQueue')->willReturn(true);

        $connectorEmailOrder = $this->getMock(
            \Dotdigitalgroup\Email\Model\Connector\Order::class,
            [],
            [],
            '',
            false
        );
        $connectorEmailOrder->method('setOrderData')->willReturn(true);
        $this->connectorOrderFactory->method('create')->willReturn($connectorEmailOrder);

        $emailOrderCollection = $objectManager->get(
            \Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection::class
        );

        $orderCollection = $objectManager->get(\Magento\Sales\Model\ResourceModel\Order\Collection::class);

        $connectorOrder = $this->getMock(\Dotdigitalgroup\Email\Model\Order::class, [], [], '', false);

        if ($withGuests) {
            $connectorOrder->method('getOrdersToImport')->willReturn($this->getMock(
                \Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection::class,
                [],
                [],
                '',
                false
            ));
            $connectorOrder->method('getModifiedOrdersToImport')->willReturn($emailOrderCollection);
        } else {
            $connectorOrder->method('getOrdersToImport')->willReturn($emailOrderCollection);
            $connectorOrder->method('getModifiedOrdersToImport')->willReturn($this->getMock(
                \Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection::class,
                [],
                [],
                '',
                false
            ));
        }
        $connectorOrder->method('getSalesOrdersWithIds')->willReturn($orderCollection);

        $this->orderFactory->method('create')->willReturn($connectorOrder);
        $this->salesOrderFactory->method('create')->willReturn($this->getMock(
            \Magento\Sales\Model\Order::class,
            [],
            [],
            '',
            false
        ));
    }

    /**
     * @return void
     */
    public function createNewEmailOrder()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $order = $objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $order = $order->getFirstItem();

        $emailOrder = $objectManager->create(\Dotdigitalgroup\Email\Model\Order::class);
        $emailOrder->setOrderId($order->getId());
        $emailOrder->setOrderStatus($order->getStatus());
        $emailOrder->setQuoteId($order->getQuoteId());
        $emailOrder->setStoreId($order->getStoreId());
        $emailOrder->setEmailImported('0');
        $emailOrder->setModified(new \Zend_Db_Expr('null'));

        $emailOrder->save();
    }

    /**
     * @return void
     */
    public function createModifiedEmailOrder()
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
