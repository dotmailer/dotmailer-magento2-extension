<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ContactData\SubscriberFactory as ConnectorSubscriberFactory;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Dotdigitalgroup\Email\Model\Sync\Export\SalesDataManager;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\ConsentDataManager;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberExporter;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberWithSalesExporter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\TestCase;

class SubscriberWithSalesExporterTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var Datafield|\PHPUnit\Framework\MockObject\MockObject
     */
    private $datafieldMock;

    /**
     * @var ConnectorSubscriberFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectorSubscriberFactoryMock;

    /**
     * @var ContactCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var SalesDataManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $salesDataManagerMock;

    /**
     * @var ConsentDataManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentDataManagerMock;

    /**
     * @var SubscriberExporter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscriberExporterMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var CsvHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $csvHandlerMock;

    /**
     * @var WebsiteInterface&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
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
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->salesDataManagerMock = $this->createMock(SalesDataManager::class);
        $this->consentDataManagerMock = $this->createMock(ConsentDataManager::class);
        $this->subscriberExporterMock = $this->createMock(SubscriberExporter::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
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

        $this->exporter = new SubscriberWithSalesExporter(
            $this->loggerMock,
            $this->datafieldMock,
            $this->connectorSubscriberFactoryMock,
            $this->contactCollectionFactoryMock,
            $this->salesDataManagerMock,
            $this->consentDataManagerMock,
            $this->subscriberExporterMock,
            $this->scopeConfigMock,
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

        /* Consent data */
        $this->consentDataManagerMock->expects($this->once())
            ->method('setSubscriberConsentData')
            ->willReturn($this->getSubscriberConsentData());

        /* Subscriber sales data */
        $this->salesDataManagerMock->expects($this->once())
            ->method('setContactSalesData')
            ->willReturn($this->getSubscriberSalesData());

        /* Set data on the model */
        $connectorSubscriberMock = $this->createMock(\Dotdigitalgroup\Email\Model\Connector\ContactData\Subscriber::class);
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
            $contactMock->expects($this->exactly(18))->method('setData');
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
