<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\DataField;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Customer\Exporter as CustomerExporter;
use Dotdigitalgroup\Email\Model\Sync\Customer\ExporterFactory as CustomerExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Guest\GuestExporterFactory as GuestExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\OrderHistoryChecker;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporter;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporter;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;

class DataFieldCollectorTest extends TestCase
{
    /**
     * @var CustomerExporterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerExporterFactoryMock;

    /**
     * @var GuestExporterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $guestExporterFactoryMock;

    /**
     * @var OrderHistoryChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderHistoryCheckerMock;

    /**
     * @var SubscriberExporterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subscriberExporterFactoryMock;

    /**
     * @var SubscriberWithSalesExporterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subscriberWithSalesExporterFactoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var Contact|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactModelMock;

    /**
     * @var Website|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteMock;

    /**
     * @var DataFieldCollector
     */
    private $dataFieldCollector;

    protected function setUp(): void
    {
        $this->customerExporterFactoryMock = $this->createMock(CustomerExporterFactory::class);
        $this->guestExporterFactoryMock = $this->createMock(GuestExporterFactory::class);
        $this->orderHistoryCheckerMock = $this->createMock(OrderHistoryChecker::class);
        $this->subscriberExporterFactoryMock = $this->createMock(SubscriberExporterFactory::class);
        $this->subscriberWithSalesExporterFactoryMock = $this->createMock(SubscriberWithSalesExporterFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->contactModelMock = $this->getMockBuilder(Contact::class)
            ->onlyMethods(['getId'])
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteMock = $this->createMock(Website::class);

        $this->dataFieldCollector = new DataFieldCollector(
            $this->customerExporterFactoryMock,
            $this->guestExporterFactoryMock,
            $this->orderHistoryCheckerMock,
            $this->subscriberExporterFactoryMock,
            $this->subscriberWithSalesExporterFactoryMock,
            $this->scopeConfigMock,
            $this->storeManagerMock
        );
    }

    public function testDataFieldsCollectedForCustomer()
    {
        $customerExporterMock = $this->createMock(CustomerExporter::class);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);

        $this->customerExporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($customerExporterMock);

        $customerExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getDummyExportData());

        $customerExporterMock->expects($this->once())
            ->method('getCsvColumns')
            ->willReturn($this->getSampleKeys());

        $this->contactModelMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(4);

        $data = $this->dataFieldCollector->collectForCustomer($this->contactModelMock, 1);

        $this->assertEquals($this->getCombinedKeysAndData(), $data);
    }

    public function testEmptyArrayReturnedIfCustomerExportFails()
    {
        $customerExporterMock = $this->createMock(CustomerExporter::class);
        $websiteMock = $this->createMock(Website::class);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->customerExporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($customerExporterMock);

        $customerExporterMock->expects($this->once())
            ->method('export')
            ->willReturn([]);

        $customerExporterMock->expects($this->never())
            ->method('getCsvColumns');

        $data = $this->dataFieldCollector->collectForCustomer($this->contactModelMock, 1);

        $this->assertEmpty($data);
    }

    public function testDataFieldsCollectedForSubscriber()
    {
        $subscriberExporterMock = $this->createMock(SubscriberExporter::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn(0);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);

        $this->subscriberExporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriberExporterMock);

        $subscriberExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getDummyExportData());

        $subscriberExporterMock->expects($this->once())
            ->method('getCsvColumns')
            ->willReturn($this->getSampleKeys());

        $this->contactModelMock->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(4);

        $data = $this->dataFieldCollector->collectForSubscriber($this->contactModelMock, 1);

        $this->assertEquals($this->getCombinedKeysAndData(), $data);
    }

    public function testSalesDataFieldsCollectedForSubscriber()
    {
        $subscriberWithSalesExporterMock = $this->createMock(SubscriberWithSalesExporter::class);

        // $isSubscriberSalesDataEnabled = 1
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn(1);

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(0);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);

        $this->orderHistoryCheckerMock->expects($this->once())
            ->method('checkInSales')
            ->willReturn(['chaz@emailsim.io']);

        $this->subscriberWithSalesExporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriberWithSalesExporterMock);

        $subscriberWithSalesExporterMock->expects($this->once())
            ->method('export')
            ->willReturn($this->getDummyExportData());

        $subscriberWithSalesExporterMock->expects($this->once())
            ->method('getCsvColumns')
            ->willReturn($this->getSampleKeys());

        $this->contactModelMock->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(4);

        $data = $this->dataFieldCollector->collectForSubscriber($this->contactModelMock, 1);

        $this->assertEquals($this->getCombinedKeysAndData(), $data);
    }

    public function testMergeFields()
    {
        $merged = $this->dataFieldCollector->mergeFields(
            $this->getDefaultDataFields(),
            $this->getExportedCustomerDataFields()
        );

        $this->assertEquals([
            [
                'Key' => 'STORE_NAME',
                'Value' => 'Chaz store',
            ],
            [
                'Key' => 'WEBSITE_NAME',
                'Value' => 'Chaz website',
            ],
            [
                'Key' => 'FIRST_NAME',
                'Value' => 'Chaz',
            ],
            [
                'Key' => 'LAST_NAME',
                'Value' => 'Kangaroo',
            ],
            [
                'Key' => 'SUBSCRIBER_STATUS',
                'Value' => 'Subscribed',
            ]
        ], $merged);
    }

    public function testConsentFieldsExtracted()
    {
        $dataFields = $this->getDummySubscriberDataFields();
        $consentFields = $this->dataFieldCollector->extractConsentFromPreparedDataFields($dataFields);

        $this->assertEquals([
            [
                'Key' => 'TEXT',
                'Value' => 'You have consented!',
            ]
        ], $consentFields);
    }

    private function getDummyExportData()
    {
        return [
            '4' => [
                'chaz@emailsim.io',
                'Html',
                'Chaz',
                'Kangaroo',
                '10'
            ]
        ];
    }

    private function getSampleKeys()
    {
        return [
            'Email',
            'EmailType',
            'FIRST_NAME',
            'LAST_NAME',
            'CUSTOMER_ID'
        ];
    }

    private function getCombinedKeysAndData()
    {
        return [
            'Email' => 'chaz@emailsim.io',
            'EmailType' => 'Html',
            'FIRST_NAME' => 'Chaz',
            'LAST_NAME' => 'Kangaroo',
            'CUSTOMER_ID' => '10'
        ];
    }

    private function getDefaultDataFields()
    {
        return [
            [
                'Key' => 'STORE_NAME',
                'Value' => 'Chaz store',
            ],
            [
                'Key' => 'WEBSITE_NAME',
                'Value' => 'Chaz website',
            ]
        ];
    }

    private function getExportedCustomerDataFields()
    {
        return [
            'Email' => 'chaz@emailsim.io',
            'EmailType' => 'Html',
            'FIRST_NAME' => 'Chaz',
            'LAST_NAME' => 'Kangaroo',
            'SUBSCRIBER_STATUS' => 'Subscribed',
            'WEBSITE_NAME' => 'Chaz website'
        ];
    }

    private function getDummySubscriberDataFields()
    {
        return [
            [
                'Key' => 'FIRST_NAME',
                'Value' => 'Chaz',
            ],
            [
                'Key' => 'LAST_NAME',
                'Value' => 'Kangaroo',
            ],
            [
                'Key' => 'SUBSCRIBER_STATUS',
                'Value' => 'Subscribed',
            ],
            [
                'Key' => 'CONSENTTEXT',
                'Value' => 'You have consented!',
            ]
        ];
    }
}
