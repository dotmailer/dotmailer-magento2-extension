<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class HistoricalOrderDataRefreshTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;
    public $model = 'Dotdigitalgroup\Email\Model\Order';
    public $url = 'backend/dotdigitalgroup_email/run/ordersreset';

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

    public function runReset($from, $to, $dispatchUrl)
    {
        $params = [
            'from' => $from,
            'to' => $to
        ];
        $this->getRequest()->setParams($params);
        $this->dispatch($dispatchUrl);
    }

    public function test_order_reset_successful_given_date_range()
    {
        $this->emptyTable();

        $data = [
            'order_id' => '1',
            'order_status' => 'pending',
            'quote_id' => '1',
            'store_id' => '1',
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->assertEquals(1, $collection->getSize());

        $this->runReset('2017-02-09', '2017-02-10', $this->url);

        $collection->addFieldToFilter('email_imported', ['null' => true]);

        $this->assertEquals(1, $collection->getSize());

    }

    public function test_order_reset_not_successful_wrong_date_range()
    {
        $this->emptyTable();

        $data = [
            'order_id' => '1',
            'order_status' => 'pending',
            'quote_id' => '1',
            'store_id' => '1',
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('email_imported', ['null' => true]);

        $this->runReset('2017-02-09', '2017-01-10', $this->url);

        $this->assertSessionMessages(
            $this->equalTo(['To Date cannot be earlier then From Date.']),
            \Magento\Framework\Message\MessageInterface::TYPE_ERROR
        );

        $this->assertEquals(0, $collection->getSize());

    }

    public function test_order_reset_not_successful_invalid_date_range()
    {
        $this->emptyTable();

        $data = [
            'order_id' => '1',
            'order_status' => 'pending',
            'quote_id' => '1',
            'store_id' => '1',
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('email_imported', ['null' => true]);

        $this->runReset('2017-02-09', 'not valid', $this->url);

        $this->assertSessionMessages(
            $this->equalTo(['From or To date is not a valid date.']),
            \Magento\Framework\Message\MessageInterface::TYPE_ERROR
        );

        $this->assertEquals(0, $collection->getSize());

    }

    public function test_order_full_reset_successful_without_date_range()
    {
        $this->emptyTable();

        $data = [
            [
                'order_id' => '1',
                'order_status' => 'pending',
                'quote_id' => '1',
                'store_id' => '1',
                'email_imported' => '1',
                'created_at' => '2017-02-09'
            ],
            [
                'order_id' => '2',
                'order_status' => 'pending',
                'quote_id' => '2',
                'store_id' => '1',
                'email_imported' => '1',
                'created_at' => '2017-02-11'
            ]
        ];
        foreach ($data as $item) {
            $this->createEmailData($item);
        }

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('email_imported', ['null' => true]);

        $this->runReset('', '', $this->url);

        $this->assertEquals(2, $collection->getSize());
    }

    public function test_order_full_reset_success_with_from_date_only()
    {
        $this->emptyTable();

        $data = [
            'order_id' => '1',
            'order_status' => 'pending',
            'quote_id' => '1',
            'store_id' => '1',
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('email_imported', ['null' => true]);

        $this->runReset('2017-02-10', '', $this->url);

        $this->assertEquals(1, $collection->getSize());
    }

    public function test_order_full_reset_success_with_to_date_only()
    {
        $this->emptyTable();

        $data = [
            'order_id' => '1',
            'order_status' => 'pending',
            'quote_id' => '1',
            'store_id' => '1',
            'email_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('email_imported', ['null' => true]);

        $this->runReset('', '2017-02-10', $this->url);

        $this->assertEquals(1, $collection->getSize());
    }

    public function createEmailData($data)
    {
        $emailModel = $this->objectManager->create($this->model);
        $emailModel->addData($data)->save();
    }

    public function emptyTable()
    {
        $resourceModel = $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Order::class);
        $resourceModel->getConnection()->truncateTable($resourceModel->getMainTable());
    }
}