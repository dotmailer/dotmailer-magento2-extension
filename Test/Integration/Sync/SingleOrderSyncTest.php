<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Class SingleOrderSyncTest
 *
 * @package Dotdigitalgroup\Email\Controller\Customer
 * @magentoDBIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SingleOrderSyncTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var string
     */
    public $storeId;

    /**
     * @var string
     */
    public $orderStatus;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection
     */
    public $importerCollection;

    /**
     * @return void
     */
    public function setup()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->importerCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection::class
        );
    }

    /**
     * @return array
     */
    public function prep()
    {
        /** @var  \Magento\Store\Model\Store $store */
        $store = $this->objectManager->create(\Magento\Store\Model\Store::class);
        $store->load($this->storeId);

        /** @var \Dotdigitalgroup\Email\Helper\Data|\PHPUnit_Framework_MockObject_MockObject $helper */
        $helper = $this->getMockBuilder(\Dotdigitalgroup\Email\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('getWebsites')->willReturn([$store->getWebsite()]);
        $helper->method('getApiUsername')->willReturn('apiuser-dummy@apiconnector.com');
        $helper->method('getApiPassword')->willReturn('dummypass');
        $helper->method('getWebsiteConfig')->willReturn('1');
        $helper->method('getConfigSelectedStatus')->willReturn($this->orderStatus);
        $helper->storeManager = $this->objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);
        $orderSync = new \Dotdigitalgroup\Email\Model\Sync\Order(
            $this->objectManager->create(\Dotdigitalgroup\Email\Model\ImporterFactory::class),
            $this->objectManager->create(\Dotdigitalgroup\Email\Model\OrderFactory::class),
            $this->objectManager->create(\Dotdigitalgroup\Email\Model\Connector\AccountFactory::class),
            $this->objectManager->create(\Dotdigitalgroup\Email\Model\Connector\OrderFactory::class),
            $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Contact::class),
            $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory::class),
            $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Order::class),
            $helper,
            $this->objectManager->create(\Magento\Sales\Model\OrderFactory::class)
        );

        return $orderSync->sync();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     *
     * @return null
     */
    public function testSingleOrderIsTypeOrderAndModeSingle()
    {
        $this->createModifiedEmailOrder();
        $this->prep();

        $item = $this->importerCollection
            ->addFieldToFilter('import_type', \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS)
            ->addFieldToFilter('import_mode', \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE)
            ->getFirstItem();

        $this->assertEquals(
            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
            $item->getImportType(),
            'Item is not type of order'
        );
        $this->assertEquals(
            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
            $item->getImportMode(),
            'Item is not single mode'
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     *
     * @return null
     */
    public function testSingleOrderTypeIsObject()
    {
        $this->createModifiedEmailOrder();
        $this->prep();
        $item = $this->importerCollection->getFirstItem();

        $this->assertInternalType('object', json_decode($item->getImportData()), 'Import data is not of object type');
    }

    /**
     * @return null
     */
    public function createModifiedEmailOrder()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        /** @var \Magento\Sales\Model\Order $order */
        $order = $orderCollection->getFirstItem();

        $this->storeId = $order->getStoreId();
        $this->orderStatus = [$order->getStatus()];

        $emailOrder = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Order::class)
            ->setOrderId($order->getId())
            ->setOrderStatus($order->getStatus())
            ->setQuoteId($order->getQuoteId())
            ->setStoreId($this->storeId)
            ->setEmailImported('1')
            ->setModified('1');

        $emailOrder->save();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSingleOrderSync()
    {
        $this->createModifiedEmailOrder();
        $orderResponse = $this->prep();

        $expected = 'Orders updated 1';

        $this->assertEquals($expected, $orderResponse['message']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSyncOrderWithoutPaymentInfoSync()
    {
        $this->createOrderWithoutPayment();
        $orderResponse = $this->prep();

        $expected = 'Orders updated 1';

        $this->assertEquals($expected, $orderResponse['message']);
    }

    private function createOrderWithoutPayment()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $orderResource = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order::class);
        /** @var \Magento\Sales\Model\Order $order */
        $order = $orderCollection->getFirstItem();
        $payment = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Sales\Model\Order\Payment::class
        );
        $order->setPayment($payment);
        $orderResource->save($order);

        $this->storeId = $order->getStoreId();
        $this->orderStatus = [$order->getStatus()];

        $emailOrder = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Order::class)
            ->setOrderId($order->getId())
            ->setOrderStatus($order->getStatus())
            ->setQuoteId($order->getQuoteId())
            ->setStoreId($this->storeId)
            ->setEmailImported('1')
            ->setModified('1');

        $emailOrder->save();
    }
}
