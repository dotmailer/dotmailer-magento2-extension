<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Class SingleOrderSyncTest
 *
 * @package Dotdigitalgroup\Email\Controller\Customer
 * @magentoDBIsolation enabled
 * magentoAppArea cron
 */
class SingleOrderSyncTest extends \PHPUnit_Framework_TestCase
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

    public function setup()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->importerCollection = $this->objectManager->create(
            'Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection'
        );
    }

    public function prep()
    {
        /** @var  \Magento\Store\Model\Store $store */
        $store = $this->objectManager->create('Magento\Store\Model\Store');
        $store->load($this->storeId);

        $helper = $this->getMock('Dotdigitalgroup\Email\Helper\Data', [], [], '', false);
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('getWebsites')->willReturn([$store->getWebsite()]);
        $helper->method('getApiUsername')->willReturn('apiuser-dummy@apiconnector.com');
        $helper->method('getApiPassword')->willReturn('dummypass');
        $helper->method('getWebsiteConfig')->willReturn('1');
        $helper->method('getConfigSelectedStatus')->willReturn($this->orderStatus);

        $orderSync = new \Dotdigitalgroup\Email\Model\Sync\Order(
            $this->objectManager->create('Dotdigitalgroup\Email\Model\ImporterFactory'),
            $this->objectManager->create('Dotdigitalgroup\Email\Model\OrderFactory'),
            $this->objectManager->create('Dotdigitalgroup\Email\Model\Connector\AccountFactory'),
            $this->objectManager->create('Dotdigitalgroup\Email\Model\Connector\OrderFactory'),
            $this->objectManager->create('Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory'),
            $this->objectManager->create('Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory'),
            $helper,
            $this->objectManager->create('Magento\Framework\App\ResourceConnection'),
            $this->objectManager->create('Magento\Sales\Model\OrderFactory'),
            $this->objectManager->create('\Magento\Store\Model\StoreManagerInterface')
        );

        $orderSync->sync();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     */
    public function test_importer_collection_count_is_one()
    {
        $this->createModifiedEmailOrder();
        $this->prep();
        $this->assertEquals(1, $this->importerCollection->getSize(), 'Item count is not one');
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     */
    public function test_single_order_is_type_order_and_mode_single()
    {
        $this->createModifiedEmailOrder();
        $this->prep();

        $item = $this->importerCollection->getFirstItem();

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
     */
    public function test_singe_order_type_is_array()
    {
        $this->createModifiedEmailOrder();
        $this->prep();
        $item = $this->importerCollection->getFirstItem();

        $this->assertInternalType('array', unserialize($item->getImportData()), 'Import data is not of array type');
    }

    public function createModifiedEmailOrder()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->objectManager->create('Magento\Sales\Model\ResourceModel\Order\Collection');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $orderCollection->getFirstItem();

        $this->storeId = $order->getStoreId();
        $this->orderStatus = [$order->getStatus()];

        $emailOrder = $this->objectManager->create('Dotdigitalgroup\Email\Model\Order')
            ->setOrderId($order->getId())
            ->setOrderStatus($order->getStatus())
            ->setQuoteId($order->getQuoteId())
            ->setStoreId($this->storeId)
            ->setEmailImported('1')
            ->setModified('1');

        $emailOrder->save();
    }
}