<?php

namespace Dotdigitalgroup\Email\Test\Integration\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Sync\Order;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;

if (!class_exists('\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory')) {
    require __DIR__ . '/../_files/product_extension_interface_hacktory.php';
}

class SingleOrderSyncTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection
     */
    private $importerCollection;

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function setUp() :void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->setApiConfigFlags([
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED => 1,
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS => implode(',', [
                OrderModel::STATE_PROCESSING,
                OrderModel::STATE_COMPLETE,
            ])
        ]);
        $this->instantiateDataHelper();

        $this->importerCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection::class
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store sync_settings/sync/order_enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     *
     * @return void
     * @throws LocalizedException
     */
    public function testSingleOrderIsTypeOrderAndModeBulk()
    {
        $order = $this->createModifiedEmailOrder();
        $this->setContactIdForOrder($order);
        $this->prep($order->getStoreId());

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
     */
    public function testSingleOrderSync(): void
    {
        $order = $this->createModifiedEmailOrder();
        $this->setContactIdForOrder($order);
        $orderResponse = $this->prep($order->getStoreId());

        $this->assertEquals(1, $orderResponse['syncedOrders']);

        $item = $this->importerCollection->getLastItem();
        $this->assertIsObject(json_decode($item->getImportData()), 'Import data is not of object type');
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSyncOrderWithoutPaymentInfoSync(): void
    {
        $order = $this->createOrderWithoutPayment();
        $this->setContactIdForOrder($order);
        $orderResponse = $this->prep($order->getStoreId());

        $this->assertEquals(1, $orderResponse['syncedOrders']);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function prep($storeId)
    {
        /** @var  Store $store */
        $store = $this->objectManager->create(Store::class);
        $store->load($storeId);

        /** @var Order $orderSync */
        $orderSync = $this->objectManager->create(Order::class);
        return $orderSync->sync();
    }

    /**
     * @return OrderModel
     * @throws \Exception
     */
    private function createModifiedEmailOrder()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        /** @var OrderModel $order */
        $order = $orderCollection->getLastItem();

        /** @var \Dotdigitalgroup\Email\Model\Order $emailOrder */
        $emailOrder = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Order::class)
            ->setOrderId($order->getId())
            ->setOrderStatus($order->getStatus())
            ->setQuoteId($order->getQuoteId())
            ->setStoreId($order->getStoreId())
            ->setProcessed('0');
        $emailOrder->save();

        return $order;
    }

    /**
     * Create an order without payment information.
     *
     * @return OrderModel
     * @throws \Exception
     */
    private function createOrderWithoutPayment()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $orderResource = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order::class);
        /** @var OrderModel $order */
        $order = $orderCollection->getLastItem();
        $payment = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Sales\Model\Order\Payment::class
        );
        $order->setPayment($payment);
        $orderResource->save($order);

        $emailOrder = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Order::class)
            ->setOrderId($order->getId())
            ->setOrderStatus($order->getStatus())
            ->setQuoteId($order->getQuoteId())
            ->setStoreId($order->getStoreId())
            ->setEmailImported('1')
            ->setModified('1');

        $emailOrder->save();

        return $order;
    }

    /**
     * Set contact ID for the order.
     *
     * @param OrderModel $order
     * @throws \Exception
     */
    private function setContactIdForOrder(OrderModel $order)
    {
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
    }
}
