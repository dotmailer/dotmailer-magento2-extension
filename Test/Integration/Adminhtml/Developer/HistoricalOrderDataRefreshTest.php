<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

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
     * @var object
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
     * @param mixed $from
     * @param mixed $to
     * @param mixed $dispatchUrl
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

    /**
     * @return void
     */
    public function testOrderResetNotSuccessfulWrongDateRange()
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

    /**
     * @return void
     */
    public function testOrderResetNotSuccessfulInvalidDateRange()
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

    /**
     * @return void
     */
    public function testOrderFullResetSuccessfulWithoutDateRange()
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

    /**
     * @return void
     */
    public function testOrderFullResetSuccessWithFromDateOnly()
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

    /**
     * @return void
     */
    public function testOrderFullResetSuccessWithToDateOnly()
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

    /**
     * @param mixed $data
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
