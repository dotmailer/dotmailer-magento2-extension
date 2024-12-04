<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Customer;

use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\Sync\Customer\CustomerDataManager;
use Magento\Customer\Model\ResourceModel\CustomerFactory as CustomerResourceFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Review\Model\ResourceModel\ReviewFactory as ReviewResourceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerDataManagerTest extends TestCase
{
    /**
     * @var CustomerResourceFactory|MockObject
     */
    private $customerResourceFactoryMock;

    /**
     * @var ContactCollectionFactory|MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var CustomerCollectionFactory|MockObject
     */
    private $customerCollectionFactoryMock;

    /**
     * @var ReviewResourceFactory|MockObject
     */
    private $reviewResourceFactoryMock;

    /**
     * @var CustomerDataManager
     */
    private $customerDataManager;

    protected function setUp() :void
    {
        $this->customerResourceFactoryMock = $this->createMock(CustomerResourceFactory::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->customerCollectionFactoryMock = $this->createMock(CustomerCollectionFactory::class);
        $this->reviewResourceFactoryMock = $this->createMock(ReviewResourceFactory::class);

        $this->customerDataManager = new CustomerDataManager(
            $this->customerResourceFactoryMock,
            $this->contactCollectionFactoryMock,
            $this->customerCollectionFactoryMock,
            $this->reviewResourceFactoryMock
        );
    }

    /**
     * This test confirms that we query last_logged_in dates for customers IF the field is mapped.
     *
     * @return void
     */
    public function testLastLoggedInDatesAreReturned()
    {
        $customerIds = [1, 2, 3, 4, 5];
        $columns = $this->getColumns();
        $results = $this->getLoggedInDataResults();

        $customerResourceModelMock = $this->createMock(\Magento\Customer\Model\ResourceModel\Customer::class);
        $this->customerResourceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($customerResourceModelMock);

        $adapterInterfaceMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $customerResourceModelMock->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($adapterInterfaceMock);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $adapterInterfaceMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);

        $selectMock->expects($this->once())->method('from')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('where')->willReturn($selectMock);
        $adapterInterfaceMock->expects($this->once())->method('fetchAll')->willReturn($results);

        $loggedInDates = $this->customerDataManager->fetchLastLoggedInDates($customerIds, $columns);

        $this->assertEquals(
            array_column($results, 'last_login_at'),
            array_column($loggedInDates, 'last_logged_date')
        );
    }

    /**
     * If LAST_LOGGEDIN_DATE is not mapped, then we don't look this up.
     *
     * @return void
     */
    public function testLastLoggedInDatesAreNotQueriedIfNotMapped()
    {
        $customerIds = [1, 2, 3, 4, 5];
        $columns = $this->getColumnsWithNoLastLoggedIn();

        $this->customerResourceFactoryMock->expects($this->never())
            ->method('create');

        $this->customerDataManager->fetchLastLoggedInDates($customerIds, $columns);
    }

    /**
     * @return void
     */
    public function testFetchReviewData()
    {
        $customerIds = [1, 2, 3, 4, 5];
        $columns = $this->getColumns();
        $results = $this->getReviewDataResults();

        $reviewResourceModelMock = $this->createMock(\Magento\Review\Model\ResourceModel\Review::class);
        $this->reviewResourceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($reviewResourceModelMock);

        $adapterInterfaceMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $reviewResourceModelMock->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($adapterInterfaceMock);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $adapterInterfaceMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);

        $selectMock->expects($this->once())->method('from')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('join')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('where')->willReturn($selectMock);
        $selectMock->expects($this->exactly(2))->method('group')->willReturn($selectMock);
        $adapterInterfaceMock->expects($this->once())->method('fetchAll')->willReturn($results);

        $reviewData = $this->customerDataManager->fetchReviewData($customerIds, $columns);

        $this->assertCount(5, $reviewData);
        $this->assertCount(1, $reviewData[1]['review_data']);
        $this->assertCount(2, $reviewData[2]['review_data']);
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        $datafield = new Datafield();
        $columns = [];

        foreach ($datafield->getContactDatafields() as $key => $properties) {
            $columns[$key] = $properties['name'];
        }

        return $columns;
    }

    /**
     * @return array[]
     */
    private function getColumnsWithNoLastLoggedIn()
    {
        return [
            [
                'customer_id' => 'CUSTOMER_ID'
            ],
            [
                'firstname' => 'FIRSTNAME'
            ],
            [
                'lastname' => 'LASTNAME'
            ],
        ];
    }

    /**
     * @return array[]
     */
    private function getLoggedInDataResults()
    {
        return [
            [
                'customer_id' => 1,
                'last_login_at' => '2021-11-03 14:55:23'
            ],
            [
                'customer_id' => 2,
                'last_login_at' => '2021-11-03 14:55:24'
            ],
            [
                'customer_id' => 3,
                'last_login_at' => '2021-11-03 14:55:25'
            ],
            [
                'customer_id' => 4,
                'last_login_at' => '2021-11-03 14:55:26'
            ],
            [
                'customer_id' => 5,
                'last_login_at' => '2021-11-03 14:55:27'
            ],
        ];
    }

    /**
     * Mock review data including a customer who has placed reviews on 2 different stores.
     *
     * @return array[]
     */
    private function getReviewDataResults()
    {
        return [
            [
                'customer_id' => 1,
                'store_id' => 1,
                'review_count' => 0,
                'last_review_date' => null
            ],
            [
                'customer_id' => 2,
                'store_id' => 1,
                'review_count' => 2,
                'last_review_date' => '2021-11-03 14:55:24'
            ],
            [
                'customer_id' => 2,
                'store_id' => 3,
                'review_count' => 1,
                'last_review_date' => '2021-11-04 14:55:24'
            ],
            [
                'customer_id' => 3,
                'store_id' => 1,
                'review_count' => 2,
                'last_review_date' => '2021-11-03 14:55:24'
            ],
            [
                'customer_id' => 4,
                'store_id' => 1,
                'review_count' => 2,
                'last_review_date' => '2021-11-03 14:55:24'
            ],
            [
                'customer_id' => 5,
                'store_id' => 1,
                'review_count' => 2,
                'last_review_date' => '2021-11-03 14:55:24'
            ],
        ];
    }
}
