<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Customer;

use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Magento\Customer\Model\ResourceModel\CustomerFactory as CustomerResourceFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Customer\CustomerDataManager;
use PHPUnit\Framework\TestCase;

class CustomerDataManagerTest extends TestCase
{
    /**
     * @var CustomerResourceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerResourceFactoryMock;

    /**
     * @var ContactCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var CustomerCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerCollectionFactoryMock;

    /**
     * @var CustomerDataManager
     */
    private $customerDataManager;

    protected function setUp() :void
    {
        $this->customerResourceFactoryMock = $this->createMock(CustomerResourceFactory::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->customerCollectionFactoryMock = $this->createMock(CustomerCollectionFactory::class);

        $this->customerDataManager = new CustomerDataManager(
            $this->customerResourceFactoryMock,
            $this->contactCollectionFactoryMock,
            $this->customerCollectionFactoryMock
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
        $results = $this->getResults();

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
    private function getResults()
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
}
