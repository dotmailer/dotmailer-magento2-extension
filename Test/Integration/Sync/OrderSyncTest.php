<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\TestFramework\ObjectManager;

/**
 * Class OrderSyncTest
 * @package Dotdigitalgroup\Email\Controller\Customer
 * @magentoDBIsolation enabled
 * magentoAppArea cron
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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    public $contactResourceFactory;
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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory \PHPUnit_Framework_MockObject_MockObject
     */
    public $orderResourceFactory;

    public function setUp()
    {
        $this->importerFactory = $this->getMock('Dotdigitalgroup\Email\Model\ImporterFactory', [], [], '', false);
        $this->account = ObjectManager::getInstance()->get('Dotdigitalgroup\Email\Model\Connector\AccountFactory');
        $this->orderFactory = $this->getMock('Dotdigitalgroup\Email\Model\OrderFactory', [], [], '', false);
        $this->connectorOrderFactory = $this->getMock('Dotdigitalgroup\Email\Model\Connector\OrderFactory', [], [], '', false);
        $this->contactResourceFactory = ObjectManager::getInstance()->get('Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory');
        $this->orderResourceFactory = ObjectManager::getInstance()->get('Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory');
        $this->helper = $this->getMock('Dotdigitalgroup\Email\Helper\Data', [], [], '', false);
        $this->resource = $this->getMock('Magento\Framework\App\ResourceConnection', [], [], '', false);
        $this->salesOrderFactory = $this->getMock('Magento\Sales\Model\OrderFactory', [], [], '', false);
        $this->storeManager = ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface');

        $this->_orderSync = new \Dotdigitalgroup\Email\Model\Sync\Order(
            $this->importerFactory,
            $this->orderFactory,
            $this->account,
            $this->connectorOrderFactory,
            $this->contactResourceFactory,
            $this->orderResourceFactory,
            $this->helper,
            $this->resource,
            $this->salesOrderFactory,
            $this->storeManager);
    }

    /**
     * Sync orders and find guest.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     */
    public function test_order_sync_can_find_guests()
    {
        $this->createNewEmailOrder();
        $this->prepareSync();


        $this->helper->expects($this->atLeastOnce())->method('getApiUsername')->willReturn('apiuser-dummy@apiconnector.com');
        $this->helper->expects($this->atLeastOnce())->method('getApiPassword')->willReturn('dummy123');
        $this->helper->expects($this->never())->method('debug');
        $this->helper->expects($this->atLeastOnce())->method('log');

        $this->_orderSync->sync();

        $this->assertNotEmpty($this->_orderSync->guests, 'Failed no guests found to sync.');
        $this->assertEquals('1', $this->_orderSync->countOrders, 'Number of orders synced not matching.');
    }


    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     */
    public function test_guests_order_not_creating_duplicates_contact()
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
     */
    public function test_guest_found_marked_is_guest()
    {
        $this->createNewEmailOrder();
        $this->prepareSync();

        $this->_orderSync->sync();
        $guests = $this->_orderSync->guests;
        $this->assertArrayHasKey('is_guest', $guests[0]);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     */
    public function test_can_sync_modified_orders()
    {
        $this->createModifiedEmailOrder();

        $this->prepareSync(true);

        $this->helper->expects($this->atLeastOnce())->method('getApiUsername')->willReturn('apiuser-dummy@apiconnector.com');
        $this->_orderSync->sync();

        $this->assertEquals('1', count($this->_orderSync->guests));
        $this->assertEquals('1', $this->_orderSync->countOrders);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareSync($withGuests = 0)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

        $website = $objectManager->create('Magento\Store\Model\Website');
        $website->setData(['code' => 'test', 'name' => 'Test Website', 'default_group_id' => '1', 'is_default' => '0']);
        $website->save();
        /** @var $store \Magento\Store\Model\Store */
        $store = $objectManager->create('Magento\Store\Model\Store');
        $store->setData([
            'code' => 'test',
            'website_id' => $website->getId(),
            'group_id' => '1',
            'name' => 'Test Store',
            'sort_order' => '0',
            'is_active' => '1'
        ]);
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

        $this->importerFactory->method('create')->willReturn($this->getMock('Dotdigitalgroup\Email\Model\Importer', ['registerQueue'], [], '', false));
        $this->importerFactory->method('registerQueue')->willReturn(true);

        $connectorEmailOrder = $this->getMock('\Dotdigitalgroup\Email\Model\Connector\Order', [], [], '' , false);
        $connectorEmailOrder->method('setOrderData')->willReturn(true);
        $this->connectorOrderFactory->method('create')->willReturn($connectorEmailOrder);

        $emailOrderCollection = $objectManager->get('\Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection');

        $orderCollection = $objectManager->get('Magento\Sales\Model\ResourceModel\Order\Collection');

        $connectorOrder = $this->getMock(\Dotdigitalgroup\Email\Model\Order::class, [], [], '', false);

        if ($withGuests) {
            $connectorOrder->method('getOrdersToImport')->willReturn($this->getMock('Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection', [], [], '', false));
            $connectorOrder->method('getModifiedOrdersToImport')->willReturn($emailOrderCollection);
        } else {

            $connectorOrder->method('getOrdersToImport')->willReturn($emailOrderCollection);
            $connectorOrder->method('getModifiedOrdersToImport')->willReturn($this->getMock('Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection', [], [], '', false));
        }
        $connectorOrder->method('getSalesOrdersWithIds')->willReturn($orderCollection);

        $this->orderFactory->method('create')->willReturn($connectorOrder);
        $this->salesOrderFactory->method('create')->willReturn($this->getMock(\Magento\Sales\Model\Order::class, [], [], '', false));

    }

    public function createNewEmailOrder()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $order = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\Collection');
        $order = $order->getFirstItem();

        $emailOrder = $objectManager->create('Dotdigitalgroup\Email\Model\Order');
        $emailOrder->setOrderId($order->getId());
        $emailOrder->setOrderStatus($order->getStatus());
        $emailOrder->setQuoteId($order->getQuoteId());
        $emailOrder->setStoreId($order->getStoreId());
        $emailOrder->setEmailImported('0');
        $emailOrder->setModified(new \Zend_Db_Expr('null'));

        $emailOrder->save();
    }

    public function createModifiedEmailOrder()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $order = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\Collection');
        $order = $order->getFirstItem();
        $emailOrder = $objectManager->create('Dotdigitalgroup\Email\Model\Order');
        $emailOrder->setOrderId($order->getId());
        $emailOrder->setOrderStatus($order->getStatus());
        $emailOrder->setQuoteId($order->getQuoteId());
        $emailOrder->setStoreId($order->getStoreId());
        $emailOrder->setEmailImported('1');
        $emailOrder->setModified('1');

        $emailOrder->save();

    }
}
