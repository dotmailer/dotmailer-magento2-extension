<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

class HistoricalOrderDataRefreshTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;
    public $model = 'Dotdigitalgroup\Email\Model\Order';
    public $url = 'dotdigitalgroup_email/run/ordersync';

    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        parent::setUp();
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
            [
                'order_id' => '1',
                'order_status' => 'pending',
                'quote_id' => '1',
                'store_id' => '1',
                'email_imported' => '1',
                'created_at' => '2017-02-09'
            ]
        ];
        foreach ($data as $item) {
            $this->createEmailData($item);
        }

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->assertEquals(1, $collection->getSize());

        $this->runReset('2017-02-09', '2017-02-10', $this->url);

        $collection->addFieldToFilter('email_imported', 1);

        $this->assertEquals(1, $collection->getSize());

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

        $this->assertEquals(2, $collection->getSize());

        $this->runReset('', '', $this->url);

        $collection->addFieldToFilter('email_imported', 1);

        $this->assertEquals(2, $collection->getSize());
    }

    public function test_order_full_reset_success_with_from_date_only()
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
            ]
        ];
        foreach ($data as $item) {
            $this->createEmailData($item);
        }

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->assertEquals(1, $collection->getSize());

        $this->runReset('2017-02-10', '', $this->url);

        $collection->addFieldToFilter('email_imported', 1);

        $this->assertEquals(1, $collection->getSize());
    }

    public function test_order_full_reset_success_with_to_date_only()
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
        ];
        foreach ($data as $item) {
            $this->createEmailData($item);
        }

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $this->assertEquals(1, $collection->getSize());

        $this->runReset('', '2017-02-10', $this->url);

        $collection->addFieldToFilter('email_imported', 1);

        $this->assertEquals(1, $collection->getSize());
    }

    public function createEmailData($data)
    {
        $emailModel = $this->objectManager->create($this->model);
        $emailModel->addData($data)->save();
    }

    public function emptyTable()
    {
        $model = $this->objectManager->create($this->model);
        $model->getResource()->getConnection()->truncateTable($model->getResource()->getMainTable());
    }
}