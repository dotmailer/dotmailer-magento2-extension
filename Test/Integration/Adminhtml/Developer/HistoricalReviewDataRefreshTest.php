<?php

namespace Dotdigitalgroup\Email\Tests\Integration\Adminhtml\Developer;

class HistoricalReviewDataRefreshTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var string
     */
    public $model = \Dotdigitalgroup\Email\Model\Review::class;

    /**
     * @var string
     */
    public $url = 'backend/dotdigitalgroup_email/run/reviewsreset';

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
    public function testReviewResetSuccessfulGivenDateRange()
    {
        $this->emptyTable();

        $data = [
            'review_id' => '1',
            'customer_id' => '1',
            'store_id' => '1',
            'review_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('review_imported', ['null' => true]);

        $this->runReset('2017-02-09', '2017-02-10', $this->url);

        $this->assertEquals(1, $collection->getSize());
    }

    /**
     * @return void
     */
    public function testReviewresetNotSuccessfulWrongDateRange()
    {
        $this->emptyTable();

        $data = [
            'review_id' => '1',
            'customer_id' => '1',
            'store_id' => '1',
            'review_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('review_imported', ['null' => true]);

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
    public function testReviewresetNotSuccessfulInvalidDateRange()
    {
        $this->emptyTable();

        $data = [
            'review_id' => '1',
            'customer_id' => '1',
            'store_id' => '1',
            'review_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('review_imported', ['null' => true]);

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
    public function testReviewFullResetSuccsesfulWithoutDateRange()
    {
        $this->emptyTable();

        $data = [
            [
                'review_id' => '1',
                'customer_id' => '1',
                'store_id' => '1',
                'review_imported' => '1',
                'created_at' => '2017-02-09'
            ],
            [
                'review_id' => '2',
                'customer_id' => '2',
                'store_id' => '1',
                'review_imported' => '1',
                'created_at' => '2017-02-11'
            ]
        ];

        foreach ($data as $item) {
            $this->createEmailData($item);
        }

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('review_imported', ['null' => true]);

        $this->runReset('', '', $this->url);

        $this->assertEquals(2, $collection->getSize());
    }

    /**
     * @return void
     */
    public function testReviewFullResetSuccessWithFromDateOnly()
    {
        $this->emptyTable();

        $data = [
            'review_id' => '1',
            'customer_id' => '1',
            'store_id' => '1',
            'review_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('review_imported', ['null' => true]);

        $this->runReset('2017-02-10', '', $this->url);

        $this->assertEquals(1, $collection->getSize());
    }

    /**
     * @return void
     */
    public function testReviewFullResetSuccessWithToDateOnly()
    {
        $this->emptyTable();

        $data = [
            'review_id' => '1',
            'customer_id' => '1',
            'store_id' => '1',
            'review_imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('review_imported', ['null' => true]);

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
        $resourceModel = $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Review::class);
        $resourceModel->getConnection()->truncateTable($resourceModel->getMainTable());
    }
}
