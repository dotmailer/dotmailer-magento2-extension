<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;

if (!class_exists('\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory')) {
    require __DIR__ . '/../_files/product_extension_interface_hacktory.php';
}

class SingleOrderSyncTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

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
    public function setUp() :void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->setApiConfigFlags([
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED => 1,
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS => implode(',', [
                \Magento\Sales\Model\Order::STATE_PROCESSING,
                \Magento\Sales\Model\Order::STATE_COMPLETE,
            ])
        ]);
        $this->instantiateDataHelper();

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

        /** @var Order $orderSync */
        $orderSync = $this->objectManager->create(Order::class);
        return $orderSync->sync();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     *
     * @return void
     */
    public function testSingleOrderIsTypeOrderAndModeBulk()
    {
        $this->createModifiedEmailOrder();
        $this->prep();

        $item = $this->importerCollection
            ->addFieldToFilter('import_type', \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS)
            ->addFieldToFilter('import_mode', \Dotdigitalgroup\Email\Model\Importer::MODE_BULK_JSON)
            ->getLastItem();

        $this->assertEquals(
            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
            $item->getImportType(),
            'Item is not type of order'
        );
        $this->assertEquals(
            \Dotdigitalgroup\Email\Model\Importer::MODE_BULK_JSON,
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
        $item = $this->importerCollection->getLastItem();

        $this->assertIsObject(json_decode($item->getImportData()), 'Import data is not of object type');
    }

    /**
     * @return null
     */
    private function createModifiedEmailOrder()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        /** @var \Magento\Sales\Model\Order $order */
        $order = $orderCollection->getLastItem();

        /** @var \Dotdigitalgroup\Email\Model\Order $emailOrder */
        $emailOrder = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Order::class)
            ->setOrderId($order->getId())
            ->setOrderStatus($order->getStatus())
            ->setQuoteId($order->getQuoteId())
            ->setStoreId($order->getStoreId())
            ->setProcessed('0');
        $emailOrder->save();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSingleOrderSync()
    {
        $this->createModifiedEmailOrder();
        $orderResponse = $this->prep();

        $this->assertEquals(1, $orderResponse['syncedOrders']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSyncOrderWithoutPaymentInfoSync()
    {
        $this->createOrderWithoutPayment();
        $orderResponse = $this->prep();

        $this->assertEquals(1, $orderResponse['syncedOrders']);
    }

    private function createOrderWithoutPayment()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $orderResource = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order::class);
        /** @var \Magento\Sales\Model\Order $order */
        $order = $orderCollection->getLastItem();
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
