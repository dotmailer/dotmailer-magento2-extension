<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\DataField;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigital\V3\Models\Contact\DataField;
use Dotdigital\V3\Models\DataFieldCollection;
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
        $contactId = 4;
        $customerExporterMock = $this->createMock(CustomerExporter::class);
        $sdkContactMock = $this->createMock(SdkContact::class);
        $dataFieldCollectionMock = $this->createMock(DataFieldCollection::class);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);

        $this->customerExporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($customerExporterMock);

        $customerExporterMock->expects($this->once())
            ->method('setFieldMapping');

        $customerExporterMock->expects($this->once())
            ->method('export')
            ->willReturn([
                $contactId => $sdkContactMock
            ]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($contactId);

        $sdkContactMock->method('__call')
            ->with('getDataFields')
            ->willReturn($dataFieldCollectionMock);

        $dataFieldCollectionMock->method('all')->willReturn($this->getExportedCustomerDataFields());

        $data = $this->dataFieldCollector->collectForCustomer($this->contactModelMock, 1, 123456);

        $this->assertEquals($this->getExportedCustomerDataFields(), $data);
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

        $data = $this->dataFieldCollector->collectForCustomer($this->contactModelMock, 1, 123456);

        $this->assertEmpty($data);
    }

    public function testDataFieldsCollectedForSubscriber()
    {
        $contactId = 4;
        $subscriberExporterMock = $this->createMock(SubscriberExporter::class);
        $sdkContactMock = $this->createMock(SdkContact::class);

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
            ->willReturn([
                $contactId => $sdkContactMock
            ]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($contactId);

        $this->dataFieldCollector->collectForSubscriber($this->contactModelMock, 1, 123456);
    }

    public function testSalesDataFieldsCollectedForSubscriber()
    {
        $contactId = 4;
        $subscriberWithSalesExporterMock = $this->createMock(SubscriberWithSalesExporter::class);
        $sdkContactMock = $this->createMock(SdkContact::class);

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
            ->willReturn([
                $contactId => $sdkContactMock
            ]);

        $this->contactModelMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($contactId);

        $this->dataFieldCollector->collectForSubscriber($this->contactModelMock, 1, 123456);
    }

    public function testMergeFields()
    {
        $merged = $this->dataFieldCollector->mergeFields(
            $this->getDefaultDataFields(),
            $this->getExportedCustomerDataFields()
        );

        $this->assertEquals(
            [
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
                ],
                [
                    'Key' => 'CUSTOMER_ID',
                    'Value' => '10',
                ]
            ],
            $merged
        );
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
            new DataField('FIRST_NAME', 'Chaz'),
            new DataField('LAST_NAME', 'Kangaroo'),
            new DataField('SUBSCRIBER_STATUS', 'Subscribed'),
            new DataField('CUSTOMER_ID', '10'),
            new DataField('WEBSITE_NAME', 'Chaz website')
        ];
    }

    private function getExportedSubscriberWithDataFields()
    {
        return new SdkContact([
            'matchIdentifier' => 'email',
            'identifiers' => [
                'email' => 'chaz@emailsim.io'
            ],
            'dataFields' => [
                new DataField('FIRST_NAME', 'Chaz'),
                new DataField('LAST_NAME', 'Kangaroo'),
                new DataField('SUBSCRIBER_STATUS', 'Subscribed'),
                new DataField('CONSENTTEXT', 'You have consented!'),
            ]
        ]);
    }

    private function getExportedSubscriberDataFieldsWithSales()
    {
        return [
            new DataField('FIRST_NAME', 'Chaz'),
            new DataField('LAST_NAME', 'Kangaroo'),
            new DataField('SUBSCRIBER_STATUS', 'Subscribed'),
            new DataField('CONSENTTEXT', 'You have consented!'),
            new DataField('NUMBER_OF_ORDERS', '5'),
        ];
    }
}
