<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Customer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\CustomerFactory as ConnectorCustomerFactory;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\Customer\CustomerDataFieldProviderFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Customer\CustomerDataManager;
use Dotdigitalgroup\Email\Model\Sync\Customer\Exporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SalesDataManager;
use Magento\Customer\Model\ResourceModel\Customer\Collection as MageCustomerCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ConnectorCustomerFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectorCustomerFactoryMock;

    /**
     * @var CustomerDataFieldProviderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerDataFieldProviderFactoryMock;

    /**
     * @var CustomerDataFieldProviderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var CustomerDataManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerDataManagerMock;

    /**
     * @var CsvHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $csvHandlerMock;

    /**
     * @var SalesDataManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $salesDataManagerMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializerMock;

    /**
     * @var Exporter
     */
    private $exporter;

    private $customerIds = [1, 2, 3, 4, 5];

    protected function setUp() :void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->connectorCustomerFactoryMock = $this->createMock(ConnectorCustomerFactory::class);
        $this->customerDataFieldProviderFactoryMock = $this->createMock(CustomerDataFieldProviderFactory::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->customerDataManagerMock = $this->createMock(CustomerDataManager::class);
        $this->csvHandlerMock = $this->createMock(CsvHandler::class);
        $this->salesDataManagerMock = $this->createMock(SalesDataManager::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
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

        $this->exporter = new Exporter(
            $this->loggerMock,
            $this->connectorCustomerFactoryMock,
            $this->customerDataFieldProviderFactoryMock,
            $this->contactCollectionFactoryMock,
            $this->customerDataManagerMock,
            $this->csvHandlerMock,
            $this->salesDataManagerMock,
            $this->scopeConfigMock,
            $this->serializerMock
        );
    }

    /**
     * This test runs all the code you would expect to be run in an export with all data fields mapped.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testExportRetrievesDataAccordingToColumns()
    {
        $customerStubs = $this->createCustomerMocks();

        $mageCustomerCollectionMock = $this->createMock(MageCustomerCollection::class);
        $this->customerDataManagerMock->expects($this->once())
            ->method('buildCustomerCollection')
            ->willReturn($mageCustomerCollectionMock);

        /* Customer scope data */
        $this->customerDataManagerMock->expects($this->once())
            ->method('setCustomerScopeData')
            ->willReturn($this->getCustomerScopeData());

        /* Last logged-in dates */
        $this->customerDataManagerMock->expects($this->once())
            ->method('fetchLastLoggedInDates')
            ->willReturn($this->getLastLoggedInDates());

        /* Customer sales data */
        $mageCustomerCollectionMock->expects($this->once())
            ->method('getColumnValues')
            ->willReturn($this->getEmails());

        $this->salesDataManagerMock->expects($this->once())
            ->method('setContactSalesData')
            ->willReturn($this->getCustomerSalesData());

        /* Loop through customer collection */
        $mageCustomerCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([
                $customerStubs[0],
                $customerStubs[1],
                $customerStubs[2],
                $customerStubs[3],
                $customerStubs[4]
            ]));

        /* Set data on the model */
        $connectorCustomerMock = $this->createMock(\Dotdigitalgroup\Email\Model\Connector\ContactData\Customer::class);
        $this->connectorCustomerFactoryMock->expects($this->exactly(5))
            ->method('create')
            ->willReturn($connectorCustomerMock);

        $connectorCustomerMock->expects($this->exactly(5))
            ->method('init')
            ->willReturn($connectorCustomerMock);

        $connectorCustomerMock->expects($this->exactly(5))
            ->method('toCSVArray')
            ->willReturn([]);

        $data = $this->exporter->export($this->customerIds, $this->websiteInterfaceMock);

        /**
         * We can't test the data that has been set on the Customer model, because
         * setData($column, $value) doesn't do anything in the context of a unit test.
         */
        $this->assertEquals(count($data), count($this->customerIds));
    }

    /**
     * @return array
     */
    private function createCustomerMocks()
    {
        $mocks = [];

        $contactIds = ['2', '4', '6', '8', '10'];

        for ($i = 1; $i <= 5; $i++) {
            $mageCustomerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
                ->onlyMethods(['getId', 'setData', 'clearInstance'])
                ->addMethods(['getEmail', 'getEmailContactId'])
                ->disableOriginalConstructor()
                ->getMock();
            $mageCustomerMock->method('getId')->willReturn($i);
            $mageCustomerMock->method('getEmail')->willReturn('chaz' . $i . '@emailsim.io');
            $mageCustomerMock->method('getEmailContactId')->willReturn($contactIds[$i-1]);
            $mageCustomerMock->expects($this->exactly(17))->method('setData');
            $mageCustomerMock->expects($this->once())->method('clearInstance');
            $mocks[] = $mageCustomerMock;
        }

        return $mocks;
    }

    /**
     * @return array
     */
    private function getCustomerScopeData()
    {
        $data = [];
        for ($i = 1; $i <= 5; $i++) {
            $data[$i] = [
                'email_contact_id' => rand(1, 99),
                'website_id' => 1,
                'store_id' => 1
            ];
        }
        return $data;
    }

    /**
     * @return array
     */
    private function getLastLoggedInDates()
    {
        $data = [];
        for ($i = 1; $i <= 5; $i++) {
            $data[$i]['last_logged_date'] = '2022-02-14 11:20:47';
        }
        return $data;
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
    private function getCustomerSalesData()
    {
        $data = [];
        foreach ($this->getEmails() as $email) {
            $data[$email] = [
                'total_spend'                      => 120.00,
                'total_refund'                     => 65.00,
                'number_of_orders'                 => 2,
                'average_order_value'              => 60.00,
                'last_order_date'                  => '2022-03-17 00:00:00',
                'first_order_id'                   => 1,
                'last_order_id'                    => 2,
                'last_increment_id'                => '00000001',
                'product_id_for_first_brand'       => 1,
                'product_id_for_last_brand'        => 2,
                'week_day'                         => 'Monday',
                'month_day'                        => 1,
                'product_id_for_most_sold_product' => 1
            ];
        }
        return $data;
    }
}
