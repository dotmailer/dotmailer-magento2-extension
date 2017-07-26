<?php

namespace Dotdigitalgroup\Email\Tests\Integration\Adminhtml\Developer;

/**
 * @magentoDataFixture Magento/Sales/_files/two_orders_for_two_diff_customers.php
 */
class HistoricalOrderDataRefreshTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var string
     */
    public $model = \Dotdigitalgroup\Email\Model\Order::class;

    /**
     * @var string
     */
    public $url = 'backend/dotdigitalgroup_email/run/ordersreset';

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->uri = $this->url;
        $this->resource = 'Dotdigitalgroup_Email::config';
        $params = [
            'from' => '',
            'to' => ''
        ];
        $this->getRequest()->setParams($params);
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $dispatchUrl
     * @return void
     */
    public function runReset($from, $to, $dispatchUrl)
    {
        $params = [
            'from' => $from,
            'to' => $to
        ];
        $this->getRequest()->setParams($params);
        $this->dispatch($dispatchUrl);
    }

    /**
     * @return void
     */
    public function testOrderResetSuccessfulGivenDateRange()
    {
        $this->emptyTable();
        /** @var \Magento\Sales\Model\Order $order */
        $order =  $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order = $order->loadByIncrementId('100000001');

        $data = [
            'order_id' => $order->getId(),
            'order_status' => 'pending',
            'quote_id' => $order->getQuoteId(),
            'store_id' => $order->getStoreId(),
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->assertEquals(1, $collection->getSize());

        $this->runReset('2017-02-09', '2017-02-10', $this->url);


        $this->assertEquals(1, $collection->getSize());

    }

    /**
     * @return void
     */
    public function testOrderResetNotSuccessfulWrongDateRange()
    {
        $this->emptyTable();

        /** @var \Magento\Sales\Model\Order $order */
        $order =  $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order = $order->loadByIncrementId('100000001');

        $data = [
            'order_id' => $order->getId(),
            'order_status' => 'pending',
            'quote_id' => $order->getQuoteId(),
            'store_id' => $order->getStoreId(),
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->runReset('2017-02-09', '2017-01-10', $this->url);

        $this->assertSessionMessages(
            $this->equalTo(['To Date cannot be earlier then From Date.']),
            \Magento\Framework\Message\MessageInterface::TYPE_ERROR
        );

        $this->assertEquals(0, $collection->getSize());

    }

    /**
     * @return void
     */
    public function testOrderResetNotSuccessfulInvalidDateRange()
    {
        $this->emptyTable();

        /** @var \Magento\Sales\Model\Order $order */
        $order =  $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order = $order->loadByIncrementId('100000001');

        $data = [
            'order_id' => $order->getId(),
            'order_status' => 'pending',
            'quote_id' => $order->getQuoteId(),
            'store_id' => $order->getStoreId(),
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('email_imported', ['null' => true]);

        $this->runReset('2017-02-09', 'not valid', $this->url);

        $this->assertEquals(0, $collection->getSize());

    }

    /**
     * @return void
     */
    public function testOrderFullResetSuccessfulWithoutDateRange()
    {
        $this->emptyTable();

        /** @var \Magento\Sales\Model\Order $order */
        $order =  $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order = $order->loadByIncrementId('100000001');

        $orderTwo =  $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $orderTwo = $orderTwo->loadByIncrementId('100000002');


        $data = [
            [
                'order_id' => $order->getId(),
                'order_status' => 'pending',
                'quote_id' => $order->getQuoteId(),
                'store_id' => $order->getStoreId(),
                'email_imported' => '1',
                'created_at' => '2017-02-09'
            ],
            [
                'order_id' => $orderTwo->getId(),
                'order_status' => 'pending',
                'quote_id' => $orderTwo->getQuoteId(),
                'store_id' => $orderTwo->getStoreId(),
                'email_imported' => '1',
                'created_at' => '2017-02-11'
            ]
        ];
        foreach ($data as $item) {
            $this->createEmailData($item);
        }

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->runReset('', '', $this->url);

        $this->assertEquals(2, $collection->getSize());
    }

    /**
     * @return void
     */
    public function testOrderFullResetSuccessWithFromDateOnly()
    {
        $this->emptyTable();

        /** @var \Magento\Sales\Model\Order $order */
        $order =  $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order = $order->loadByIncrementId('100000001');

        $data = [
            'order_id' => $order->getId(),
            'order_status' => 'pending',
            'quote_id' => $order->getQuoteId(),
            'store_id' => $order->getStoreId(),
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->runReset('2017-02-10', '', $this->url);

        $this->assertEquals(1, $collection->getSize());
    }

    /**
     * @return void
     */
    public function testOrderFullResetSuccessWithToDateOnly()
    {
        $this->emptyTable();
        /** @var \Magento\Sales\Model\Order $order */
        $order =  $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order = $order->loadByIncrementId('100000001');

        $data = [
            'order_id' => $order->getId(),
            'order_status' => $order->getStatus(),
            'quote_id' => $order->getQuoteId(),
            'store_id' => $order->getStoreId(),
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->runReset('', '2017-02-10', $this->url);

        $this->assertEquals(1, $collection->getSize());
    }

    /**
     * @param array $data
     * @return void
     */
    public function createEmailData($data)
    {
        $emailModel = $this->objectManager->create($this->model);
        $emailModel->addData($data)->save();
    }

    /**
     * @return void
     */
    public function emptyTable()
    {
        $resourceModel = $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Order::class);
        $resourceModel->getConnection()->truncateTable($resourceModel->getMainTable());
    }
}
