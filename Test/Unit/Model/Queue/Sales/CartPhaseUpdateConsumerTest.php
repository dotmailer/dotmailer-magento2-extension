<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Sales;

use Dotdigitalgroup\Email\Model\Queue\Sales\CartPhaseUpdateConsumer;
use Dotdigitalgroup\Email\Model\Queue\Data\CartPhaseUpdateData;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection as AutomationCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigital\Resources\AbstractResource;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartPhaseUpdateConsumerTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Data|MockObject
     */
    private $cartInsightDataMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $automationCollectionFactoryMock;

    /**
     * @var QuoteRepository|MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var AbstractResource|MockObject
     */
    private $abstractResourceMock;

    /**
     * @var CartPhaseUpdateConsumer
     */
    private $cartPhaseUpdateConsumer;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->cartInsightDataMock = $this->createMock(Data::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->automationCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->quoteRepositoryMock = $this->createMock(QuoteRepository::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->abstractResourceMock = $this->getMockBuilder(AbstractResource::class)
            ->addMethods(['createOrUpdateContactCollectionRecord'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartPhaseUpdateConsumer = new CartPhaseUpdateConsumer(
            $this->loggerMock,
            $this->cartInsightDataMock,
            $this->clientFactoryMock,
            $this->automationCollectionFactoryMock,
            $this->quoteRepositoryMock,
            $this->storeManagerMock
        );
    }

    public function testProcess()
    {
        $quoteId = 1;
        $storeId = 1;
        $websiteId = 1;

        $messageData = new CartPhaseUpdateData();
        $messageData->setQuoteId($quoteId);
        $messageData->setStoreId($storeId);

        $automationCollectionMock = $this->createMock(AutomationCollection::class);
        $this->automationCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($automationCollectionMock);

        $automationCollectionMock->expects($this->once())
            ->method('getAbandonedCartAutomationByQuoteId')
            ->with($quoteId)
            ->willReturn($automationCollectionMock);

        $automationCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->automationCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($automationCollectionMock);

        $quoteMock = $this->createMock(Quote::class);
        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willReturn($quoteMock);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($storeMock);

        $sdkClientMock = $this->createMock(Client::class);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkClientMock);

        $sdkClientMock->expects($this->once())
            ->method('__get')
            ->willReturn($this->abstractResourceMock);

        $this->abstractResourceMock->expects($this->once())
            ->method('createOrUpdateContactCollectionRecord');

        $this->cartPhaseUpdateConsumer->process($messageData);
    }

    public function testProcessExitsIfNoAutomationFound()
    {
        $quoteId = 1;
        $storeId = 1;

        $messageData = new CartPhaseUpdateData();
        $messageData->setQuoteId($quoteId);
        $messageData->setStoreId($storeId);

        $automationCollectionMock = $this->createMock(AutomationCollection::class);
        $this->automationCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($automationCollectionMock);

        $automationCollectionMock->expects($this->once())
            ->method('getAbandonedCartAutomationByQuoteId')
            ->with($quoteId)
            ->willReturn($automationCollectionMock);

        $automationCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $this->quoteRepositoryMock->expects($this->never())
            ->method('get');

        $this->storeManagerMock->expects($this->never())
            ->method('getStore');

        $this->clientFactoryMock->expects($this->never())
            ->method('create');

        $this->cartPhaseUpdateConsumer->process($messageData);
    }
}
