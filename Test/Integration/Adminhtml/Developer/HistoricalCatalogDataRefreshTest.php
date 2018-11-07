<?php

namespace Dotdigitalgroup\Email\Tests\Integration\Adminhtml\Developer;

/**
 * @magentoDataFixture Magento/Catalog/_files/products.php
 */
class HistoricalCatalogDataRefreshTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var string
     */
    public $model = \Dotdigitalgroup\Email\Model\Catalog::class;

    /**
     * @var string
     */
    public $url = 'backend/dotdigitalgroup_email/run/catalogreset';

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
    public function testCatalogResetSuccessfulGivenDateRange()
    {
        $this->emptyTable();

        $data = [
            'product_id' => '1',
            'imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $this->runReset('2017-02-09', '2017-02-10', $this->url);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();

        $collection->addFieldToFilter('imported', ['null' => true]);

        $this->assertEquals(1, $collection->getSize());
    }

    /**
     * @return void
     */
    public function testCatalogResetNotSuccessfulWrongDateRange()
    {
        $this->emptyTable();

        $data = [
            'product_id' => '1',
            'imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('imported', ['null' => true]);

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
    public function testCatalogResetNotSuccessfulInvalidDateRange()
    {
        $this->emptyTable();

        $data = [
            'product_id' => '1',
            'imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('imported', ['null' => true]);

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
    public function testCatalogFullResetSuccessfulWithoutDateRange()
    {
        $this->emptyTable();

        $data = [
            [
                'product_id' => '1',
                'imported' => '1',
                'created_at' => '2017-02-09'
            ],
            [
                'product_id' => '2',
                'imported' => '1',
                'created_at' => '2017-02-11'
            ]
        ];

        foreach ($data as $item) {
            $this->createEmailData($item);
        }

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('imported', ['null' => true]);

        $this->runReset('', '', $this->url);

        $this->assertEquals(2, $collection->getSize());
    }

    /**
     * @return void
     */
    public function testCatalogFullResetSuccessWithFromDateOnly()
    {
        $this->emptyTable();

        $data = [
            'product_id' => '1',
            'imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('imported', ['null' => true]);

        $this->runReset('2017-02-10', '', $this->url);

        $this->assertEquals(1, $collection->getSize());
    }

    /**
     * @return void
     */
    public function testCatalogFullResetSuccessWithToDateOnly()
    {
        $this->emptyTable();

        $data = [
            'product_id' => '1',
            'imported' => '1',
            'created_at' => '2017-02-09'
        ];
        $this->createEmailData($data);

        $collection = $this->objectManager->create($this->model)
            ->getCollection();
        $collection->addFieldToFilter('imported', ['null' => true]);

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
        $collection = $this->objectManager->create($this->model)
                                          ->getCollection();
        foreach ($collection as $collectionItem) {
            $collectionItem->delete();
        }
    }
}
