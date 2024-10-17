<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\Subscriber;
use Dotdigitalgroup\Email\Model\Connector\ContactData\SubscriberFactory as ConnectorSubscriberFactory;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Export\CategoryNameFinder;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkContactBuilder;
use Dotdigitalgroup\Email\Model\Sync\Export\SalesDataManager;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriberWithSalesExporterTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Datafield|MockObject
     */
    private $datafieldMock;

    /**
     * @var OptInTypeFinder|MockObject
     */
    private $optInTypeFinderMock;

    /**
     * @var ConnectorSubscriberFactory|MockObject
     */
    private $connectorSubscriberFactoryMock;

    /**
     * @var ContactCollectionFactory|MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var SalesDataManager|MockObject
     */
    private $salesDataManagerMock;

    /**
     * @var SubscriberExporterFactory|MockObject
     */
    private $subscriberExporterFactoryMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var CategoryNameFinder|MockObject
     */
    private $categoryNameFinderMock;

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
     * @var SubscriberWithSalesExporter
     */
    private $exporter;

    protected function setUp() :void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->datafieldMock = $this->createMock(Datafield::class);
        $this->connectorSubscriberFactoryMock = $this->createMock(ConnectorSubscriberFactory::class);
        $this->optInTypeFinderMock = $this->createMock(OptInTypeFinder::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->salesDataManagerMock = $this->createMock(SalesDataManager::class);
        $this->subscriberExporterFactoryMock = $this->createMock(SubscriberExporterFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->categoryNameFinderMock = $this->createMock(CategoryNameFinder::class);
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

        $this->exporter = new SubscriberWithSalesExporter(
            $this->loggerMock,
            $this->datafieldMock,
            $this->connectorSubscriberFactoryMock,
            $this->optInTypeFinderMock,
            $this->contactCollectionFactoryMock,
            $this->categoryNameFinderMock,
            $this->salesDataManagerMock,
            $this->sdkContactBuilderMock,
            $this->subscriberExporterFactoryMock,
            $this->scopeConfigMock,
            $this->csvHandlerMock,
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

        $this->categoryNameFinderMock->expects($this->once())
            ->method('getCategoryNamesByStore')
            ->willReturn([]);

        /* Subscriber sales data */
        $this->salesDataManagerMock->expects($this->once())
            ->method('setContactSalesData')
            ->willReturn($this->getSubscriberSalesData());

        /* Set data on the model */
        $connectorSubscriberMock = $this->createMock(Subscriber::class);
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

        $this->optInTypeFinderMock->expects($this->exactly(5))
            ->method('getOptInType')
            ->willReturn('double');

        $data = $this->exporter->export(
            $this->getSubscribers(),
            $this->websiteInterfaceMock,
            123456
        );

        $this->assertEquals(count($data), count($this->getSubscribers()));
    }

    /**
     * @return string[]
     */
    private function getSubscribers()
    {
        return [
            34 => 'chaz1@emailsim.io',
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
            $contactMock = $this->getMockBuilder(\Dotdigitalgroup\Email\Model\Contact::class)
                ->onlyMethods(['getId', 'setData', 'clearInstance'])
                ->addMethods(['getEmail', 'getEmailContactId'])
                ->disableOriginalConstructor()
                ->getMock();
            $contactMock->method('getId')->willReturn($i);
            $contactMock->method('getEmail')->willReturn('chaz' . $i . '@emailsim.io');
            $contactMock->method('getEmailContactId')->willReturn(rand(1, 99));
            $contactMock->expects($this->exactly(13))->method('setData');
            $contactMock->expects($this->once())->method('clearInstance');
            $mocks[] = $contactMock;
        }

        return $mocks;
    }

    /**
     * @return array
     */
    private function getSubscriberConsentData()
    {
        $data = [];
        for ($i = 1; $i <= 5; $i++) {
            $data[$i] = [
                'consent_url' => 'http://yousite.com',
                'consent_ip' => '123.0.0.0',
                'consent_user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'consent_text' => 'You signed up for some wrong mail',
                'consent_datetime' => '2022-06-01 10:41:26'
            ];
        }
        return $data;
    }

    /**
     * @return array
     */
    private function getSubscriberSalesData()
    {
        $data = [];
        foreach ($this->getSubscribers() as $email) {
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
