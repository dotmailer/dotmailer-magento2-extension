<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Export;

use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\Sync\Export\SalesDataManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as SalesOrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\TestCase;

class SalesDataManagerTest extends TestCase
{
    /**
     * @var Datafield|\PHPUnit\Framework\MockObject\MockObject
     */
    private $datafieldMock;

    /**
     * @var QuoteCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteCollectionFactoryMock;

    /**
     * @var SalesOrderCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $salesOrderCollectionFactoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var SalesDataManager
     */
    private $salesDataManager;

    /**
     * @var int[]
     */
    private $customerIds = [1, 2, 3, 4, 5];

    protected function setUp() :void
    {
        $this->datafieldMock = $this->createMock(Datafield::class);
        $this->salesOrderCollectionFactoryMock = $this->createMock(SalesOrderCollectionFactory::class);
        $this->quoteCollectionFactoryMock = $this->createMock(QuoteCollectionFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->websiteInterfaceMock = $this->getMockBuilder(WebsiteInterface::class)
            ->onlyMethods([
                'getId',
                'setId',
                'getCode',
                'setCode',
                'getName',
                'setName',
                'getDefaultGroupId',
                'setDefaultGroupId',
                'getExtensionAttributes',
                'setExtensionAttributes'
            ])
            ->addMethods(['getStoreIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->salesDataManager = new SalesDataManager(
            $this->datafieldMock,
            $this->salesOrderCollectionFactoryMock,
            $this->quoteCollectionFactoryMock,
            $this->scopeConfigMock
        );
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testSalesDataRetrievalIfAllSalesDataColumnsAreMapped()
    {
        $emails = $this->getEmails();
        $columns = $this->getColumns();
        $orderStubs = $this->createOrderStubs();
        $quoteStubs = $this->createQuoteStubs();
        $results = $this->getSalesDataResults();

        $this->websiteInterfaceMock->expects($this->atLeastOnce())->method('getStoreIds')->willReturn([1, 2]);
        $this->datafieldMock->expects($this->any())->method('getSalesDatafields')->willReturn($this->getSalesDataFields());

        $salesOrderCollectionMock = $this->createMock(SalesOrderCollection::class);
        $this->salesOrderCollectionFactoryMock->expects($this->any())->method('create')->willReturn($salesOrderCollectionMock);
        $salesOrderCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturn($salesOrderCollectionMock);
        $salesOrderCollectionMock->expects($this->any())->method('addExpressionFieldToSelect')->willReturn($salesOrderCollectionMock);
        $salesOrderCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturn($salesOrderCollectionMock);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $salesOrderCollectionMock->expects($this->any())->method('getSelect')->willReturn($selectMock);

        /* Loop through sales order collection */
        $salesOrderCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([
                $orderStubs[0],
                $orderStubs[1],
                $orderStubs[2],
                $orderStubs[3],
                $orderStubs[4]
            ]));

        $orderResourceModelMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order::class);
        $salesOrderCollectionMock->expects($this->any())->method('getResource')->willReturn($orderResourceModelMock);
        $adapterInterfaceMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $orderResourceModelMock->expects($this->any())->method('getConnection')->willReturn($adapterInterfaceMock);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $adapterInterfaceMock->expects($this->any())->method('select')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('joinLeft')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('from')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('where')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('having')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('order')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('limit')->willReturn($selectMock);
        $adapterInterfaceMock->expects($this->any())->method('fetchAll')->willReturn($results);

        $quoteCollectionMock = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $this->quoteCollectionFactoryMock->expects($this->any())->method('create')->willReturn($quoteCollectionMock);
        $quoteCollectionMock->expects($this->any())->method('addExpressionFieldToSelect')->willReturn($quoteCollectionMock);
        $quoteCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturn($quoteCollectionMock);
        $quoteCollectionMock->expects($this->any())->method('getSelect')->willReturn($selectMock);

        /* Loop through quote collection */
        $quoteCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([
                $quoteStubs[0],
                $quoteStubs[1],
                $quoteStubs[2],
                $quoteStubs[3],
                $quoteStubs[4]
            ]));

        $salesDataArray = $this->salesDataManager->setContactSalesData($emails, $this->websiteInterfaceMock, $columns);

        $this->assertArrayHasKey('total_spend', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('number_of_orders', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('average_order_value', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('last_order_date', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('first_order_id', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('last_order_id', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('last_increment_id', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('product_id_for_first_brand', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('product_id_for_last_brand', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('week_day', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('month', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('product_id_for_most_sold_product', $salesDataArray['chaz1@emailsim.io']);
        $this->assertArrayHasKey('last_quote_id', $salesDataArray['chaz1@emailsim.io']);
    }

    /**
     * The same test, but without getting sales data if none of those fields are mapped.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testSalesDataRetrievalIfNoSalesDataColumnsAreMapped()
    {
        $customerIds = [1, 2, 3, 4, 5];
        $columns = $this->getColumnsWithNoSalesDataFields();

        $this->datafieldMock->expects($this->any())->method('getSalesDatafields')->willReturn($this->getSalesDataFields());

        $this->salesOrderCollectionFactoryMock->expects($this->never())->method('create');

        $salesDataArray = $this->salesDataManager->setContactSalesData($customerIds, $this->websiteInterfaceMock, $columns);

        $this->assertEmpty($salesDataArray);
    }

    /**
     * @return string[]
     */
    private function getEmails()
    {
        return [
            'chaz1@emailsim.io',
            'chaz2@emailsim.io',
            'chaz3@emailsim.io',
            'chaz4@emailsim.io',
            'chaz5@emailsim.io'
        ];
    }

    /**
     * @return array
     */
    private function createOrderStubs()
    {
        $stubs = [];

        for ($i = 1; $i <= 5; $i++) {
            $mageOrderStub = $this->createStub(\Magento\Sales\Model\Order::class);
            $mageOrderStub->method('getCustomerEmail')->willReturn('chaz'.$i.'@emailsim.io');
            $mageOrderStub->method('toArray')->willReturn([
                'total_spend' => 120.00,
                'number_of_orders' => 2,
                'average_order_value' => 60.00,
                'last_order_date' => '2022-03-17 00:00:00',
                'first_order_id' => 1,
                'last_order_id' => 2,
                'last_increment_id' => '00000001'
            ]);
            $stubs[] = $mageOrderStub;
        }

        return $stubs;
    }

    /**
     * @return array
     */
    private function createQuoteStubs()
    {
        $stubs = [];

        for ($i = 1; $i <= 5; $i++) {
            $mageQuoteStub = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
                ->onlyMethods(['toArray'])
                ->addMethods(['getCustomerEmail'])
                ->disableOriginalConstructor()
                ->getMock();
            $mageQuoteStub->method('getCustomerEmail')->willReturn('chaz'.$i.'@emailsim.io');
            $mageQuoteStub->method('toArray')->willReturn(['last_quote_id' => 1]);
            $stubs[] = $mageQuoteStub;
        }

        return $stubs;
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
    private function getColumnsWithNoSalesDataFields()
    {
        $datafield = new Datafield();
        $salesDataFields = $datafield->getSalesDatafields();
        $columns = [];

        foreach ($datafield->getContactDatafields() as $key => $properties) {
            if (!in_array($key, array_keys($salesDataFields))) {
                $columns[ $key ] = $properties['name'];
            }
        }

        return $columns;
    }

    /**
     * @return array
     */
    private function getSalesDataFields()
    {
        $datafield = new Datafield();
        return $datafield->getSalesDatafields();
    }

    /**
     * @return array
     */
    private function getSalesDataResults()
    {
        $data = [];
        foreach ($this->customerIds as $id) {
            $data[$id] = [
                'customer_id' => $id,
                'customer_email' => 'chaz'.$id.'@emailsim.io',
                'product_id' => 1,
                'week_day' => 'Monday',
                'month' => 'February',
            ];
        }
        return $data;
    }
}
