<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\SubscriberFactory as ConnectorSubscriberFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporter;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\TestCase;

class SubscriberExporterTest extends TestCase
{
    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ConnectorSubscriberFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectorSubscriberFactoryMock;

    /**
     * @var ContactCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var CsvHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $csvHandlerMock;

    /**
     * @var WebsiteInterface&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var SubscriberExporter
     */
    private $exporter;

    protected function setUp() :void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->connectorSubscriberFactoryMock = $this->createMock(ConnectorSubscriberFactory::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->csvHandlerMock = $this->createMock(CsvHandler::class);

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
            ->addMethods(['getStoreIds', 'getConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->exporter = new SubscriberExporter(
            $this->configMock,
            $this->loggerMock,
            $this->connectorSubscriberFactoryMock,
            $this->contactCollectionFactoryMock,
            $this->csvHandlerMock
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
        $subscriberMocks = $this->createSubscriberMocks();

        $subscriberCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriberCollectionMock);

        $subscriberCollectionMock->expects($this->once())
            ->method('getContactsByContactIds')
            ->willReturn($subscriberCollectionMock);

        /* Loop through subscriber collection */
        $subscriberCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([
                $subscriberMocks[0],
                $subscriberMocks[1],
                $subscriberMocks[2],
                $subscriberMocks[3],
                $subscriberMocks[4]
            ]));

        /* Set data on the model */
        $connectorSubscriberMock = $this->createMock(
            \Dotdigitalgroup\Email\Model\Connector\ContactData\Subscriber::class
        );

        $this->connectorSubscriberFactoryMock->expects($this->exactly(5))
            ->method('create')
            ->willReturn($connectorSubscriberMock);

        $connectorSubscriberMock->expects($this->exactly(5))
            ->method('init')
            ->willReturn($connectorSubscriberMock);

        $connectorSubscriberMock->expects($this->exactly(5))
            ->method('setContactData')
            ->willReturn($connectorSubscriberMock);

        $connectorSubscriberMock->expects($this->exactly(5))
            ->method('toCSVArray')
            ->willReturn([]);

        $data = $this->exporter->export($this->getSubscribers(), $this->websiteInterfaceMock);

        /**
         * We can't test the data that has been set on the Customer model, because
         * setData($column, $value) doesn't do anything in the context of a unit test.
         */
        $this->assertEquals(count($data), count($this->getSubscribers()));
    }

    public function testDefaultCsvColumns()
    {
        $this->websiteInterfaceMock->expects($this->exactly(5))
            ->method('getConfig')
            ->willReturnOnConsecutiveCalls(
                'STORE_NAME',
                'STORE_NAME_ADDITIONAL',
                'WEBSITE_NAME',
                'SUBSCRIBER_STATUS',
                0
            );

        $this->exporter->setCsvColumns($this->websiteInterfaceMock);

        $this->assertEquals($this->getColumns(), $this->exporter->getCsvColumns());
    }

    public function testOptInTypeColumnIsAddedIfConfigured()
    {
        $this->websiteInterfaceMock->expects($this->exactly(5))
            ->method('getConfig')
            ->willReturnOnConsecutiveCalls(
                'STORE_NAME',
                'STORE_NAME_ADDITIONAL',
                'WEBSITE_NAME',
                'SUBSCRIBER_STATUS',
                1
            );

        $this->exporter->setCsvColumns($this->websiteInterfaceMock);

        $this->assertEquals($this->getColumnsWithOptInType(), $this->exporter->getCsvColumns());
    }

    /**
     * @return string[]
     */
    private function getSubscribers()
    {
        return [
            34 => 'chaz@emailsim.io',
            139 => 'chaz2@emailsim.io',
            51 => 'chaz3@emailsim.io',
            98 => 'chaz4@emailsim.io',
            357 => 'chaz5@emailsim.io',
        ];
    }

    /**
     * @return array
     */
    private function createSubscriberMocks()
    {
        $mocks = [];

        for ($i = 1; $i <= 5; $i++) {
            $mageCustomerMock = $this->getMockBuilder(\Dotdigitalgroup\Email\Model\Contact::class)
                ->onlyMethods(['getId', 'setData', 'clearInstance'])
                ->addMethods(['getEmail', 'getEmailContactId'])
                ->disableOriginalConstructor()
                ->getMock();
            $mageCustomerMock->method('getId')->willReturn($i);
            $mageCustomerMock->method('getEmail')->willReturn('chaz' . $i . '@emailsim.io');
            $mageCustomerMock->method('getEmailContactId')->willReturn(rand(1, 99));
            $mageCustomerMock->expects($this->exactly(0))->method('setData');
            $mageCustomerMock->expects($this->once())->method('clearInstance');
            $mocks[] = $mageCustomerMock;
        }

        return $mocks;
    }

    private function getColumns()
    {
        $defaultFields = [
            'store_name' => 'STORE_NAME',
            'store_name_additional' => 'STORE_NAME_ADDITIONAL',
            'website_name' => 'WEBSITE_NAME',
            'subscriber_status' => 'SUBSCRIBER_STATUS',
        ];

        return AbstractExporter::EMAIL_FIELDS + $defaultFields;
    }

    private function getColumnsWithOptInType()
    {
        return $this->getColumns() + ['opt_in_type' => 'OptInType'];
    }

    private function getAllPossibleColumns()
    {
        return $this->getColumnsWithOptInType();
    }
}
