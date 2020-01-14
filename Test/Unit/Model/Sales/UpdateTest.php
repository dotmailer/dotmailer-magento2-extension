<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sales;

use Dotdigitalgroup\Email\Model\Sales\CartInsight\Update;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection;
use Dotdigitalgroup\Email\Model\Importer;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    /**
     * @var Update
     */
    private $update;

    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationFactoryMock;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $abandonedCartDataMock;

    /**
     * @var ImporterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importerFactoryMock;

    /**
     * @var QuoteRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var StoreInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeInterfaceMock;

    /**
     * @var Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionMock;

    /**
     * @var Importer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importerMock;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerInterfaceMock;

    protected function setUp()
    {
        $this->automationFactoryMock = $this->createMock(CollectionFactory::class);
        $this->quoteRepositoryMock = $this->createMock(QuoteRepository::class);
        $this->abandonedCartDataMock = $this->createMock(Data::class);
        $this->importerFactoryMock = $this->createMock(ImporterFactory::class);
        $this->orderMock = $this->createMock(Order::class);
        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getWebsite'])
            ->getMockForAbstractClass();
        $this->collectionMock = $this->createMock(Collection::class);
        $this->importerMock = $this->createMock(Importer::class);
        $this->storeManagerInterfaceMock = $this->createMock(StoreManagerInterface::class);

        $this->update = new Update(
            $this->automationFactoryMock,
            $this->quoteRepositoryMock,
            $this->abandonedCartDataMock,
            $this->importerFactoryMock
        );
    }

    public function testUpdateCartPhaseTriggeredIfWeGotAutomation()
    {
        $orderQuoteId = 1988;
        $websiteId = 1;
        $getData = $this->getData();

        $this->automationFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->orderMock->expects($this->exactly(2))
            ->method('getQuoteId')
            ->willReturn($orderQuoteId);

        $this->collectionMock->expects($this->once())
            ->method('getAbandonedCartAutomationByQuoteId')
            ->with($orderQuoteId)
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderQuoteId)
            ->willReturn($this->quoteRepositoryMock);

        $this->abandonedCartDataMock->expects($this->once())
            ->method('getPayLoad')
            ->with(
                $this->quoteRepositoryMock,
                $this->storeInterfaceMock
            )
            ->willReturn($getData);

        $this->importerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->importerMock);

        $this->storeInterfaceMock->expects($this->atLeastOnce())
            ->method('getWebsite')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->once())
            ->method('getId')
            ->willReturn($websiteId);

        $getData['json']['cartPhase'] = 'ORDER_COMPLETE';

        $this->importerMock->expects($this->once())
            ->method('registerQueue')
            ->with(
                \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CART_INSIGHT_CART_PHASE,
                $getData,
                \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                $websiteId
            )
            ->willReturn(true);

        $this->update->updateCartPhase($this->orderMock, $this->storeInterfaceMock);
    }

    public function testUpdateCartPhaseNeverTriggeredIfWeDontHaveAutomation()
    {
        $orderQuoteId = 1988;
        $websiteId = 1;

        $this->automationFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->orderMock->expects($this->exactly(1))
            ->method('getQuoteId')
            ->willReturn($orderQuoteId);

        $this->collectionMock->expects($this->once())
            ->method('getAbandonedCartAutomationByQuoteId')
            ->with($orderQuoteId)
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $this->quoteRepositoryMock->expects($this->never())
            ->method('get');

        $this->abandonedCartDataMock->expects($this->never())
            ->method('getPayLoad');

        $this->importerFactoryMock->expects($this->never())
            ->method('create');

        $this->storeInterfaceMock->expects($this->never())
            ->method('getWebsite')
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->never())
            ->method('getId')
            ->willReturn($websiteId);

        $this->importerMock->expects($this->never())
            ->method('registerQueue');

        $this->update->updateCartPhase($this->orderMock, $this->storeInterfaceMock);
    }
    /**
     * Returns PayloadData
     * @return array
     */
    private function getData()
    {
        return $data = [
            'key' => 1,
            'contactIdentifier' => 'testContactIdentifier',
            'json' => [
                'cartId' => 1,
                'cartUrl' => 'http://sampleurl.io/cartid/12',
                'createdDate' => 'sampleDate',
                'modifiedDate' => 'sampleDate',
                'currency' => 'GBP',
                'subTotal' => '120.00',
                'taxAmount' => '20.00',
                'grandTotal' => '140.00'
            ]
        ];
    }
}
