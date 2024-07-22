<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\SubscriberFactory as ConnectorSubscriberFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\AbstractExporter;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporter;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriberExporterTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ConnectorSubscriberFactory|MockObject
     */
    private $connectorSubscriberFactoryMock;

    /**
     * @var ContactCollectionFactory|MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var CsvHandler|MockObject
     */
    private $csvHandlerMock;

    /**
     * @var SdkContactBuilder|MockObject
     */
    private $sdkContactBuilderMock;

    /**
     * @var WebsiteInterface&MockObject|MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var SubscriberExporter
     */
    private $exporter;

    protected function setUp() :void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->connectorSubscriberFactoryMock = $this->createMock(ConnectorSubscriberFactory::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->csvHandlerMock = $this->createMock(CsvHandler::class);
        $this->sdkContactBuilderMock = $this->createMock(SdkContactBuilder::class);

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
            $this->loggerMock,
            $this->connectorSubscriberFactoryMock,
            $this->contactCollectionFactoryMock,
            $this->csvHandlerMock,
            $this->sdkContactBuilderMock
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
        $sdkContactMock = $this->createMock(SdkContact::class);

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

        $this->sdkContactBuilderMock->expects($this->exactly(5))
            ->method('createSdkContact')
            ->willReturn($sdkContactMock);

        $data = $this->exporter->export(
            $this->getSubscribers(),
            $this->websiteInterfaceMock,
            123456
        );

        $this->assertEquals(count($data), count($this->getSubscribers()));
    }

    public function testDefaultFieldMapping()
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

        $this->exporter->setFieldMapping($this->websiteInterfaceMock);

        $this->assertEquals($this->getFieldMapping(), $this->exporter->getFieldMapping());
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

        $this->exporter->setFieldMapping($this->websiteInterfaceMock);

        $this->assertEquals($this->getFieldMappingWithOptInType(), $this->exporter->getFieldMapping());
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

    private function getFieldMapping()
    {
        $defaultFields = [
            'store_name' => 'STORE_NAME',
            'store_name_additional' => 'STORE_NAME_ADDITIONAL',
            'website_name' => 'WEBSITE_NAME',
            'subscriber_status' => 'SUBSCRIBER_STATUS',
        ];

        return AbstractExporter::EMAIL_FIELDS + $defaultFields;
    }

    private function getFieldMappingWithOptInType()
    {
        return $this->getFieldMapping() + ['opt_in_type' => 'OptInType'];
    }

    private function getAllPossibleFields()
    {
        return $this->getFieldMappingWithOptInType();
    }
}
