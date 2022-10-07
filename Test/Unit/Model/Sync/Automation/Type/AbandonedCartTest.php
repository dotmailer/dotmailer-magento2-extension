<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection as AutomationCollection;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DotdigitalQuoteFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCart as AbandonedCartUpdater;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCartFactory as AbandonedCartUpdaterFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\Type\AbandonedCart;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * This test is essentially a further test for Sync\Automation\AutomationProcessor,
 * which AbandonedCart extends.
 */
class AbandonedCartTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var AutomationResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationResourceMock;

    /**
     * @var DataFieldUpdateHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdateHandlerMock;

    /**
     * @var AbandonedCartUpdater|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdaterMock;

    /**
     * @var AbandonedCartUpdaterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdaterFactoryMock;

    /**
     * @var DotdigitalQuoteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ddgQuoteFactoryMock;

    /**
     * @var QuoteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteFactoryMock;

    /**
     * @var AbandonedCart
     */
    private $abandonedCart;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->dataFieldUpdateHandlerMock = $this->createMock(DataFieldUpdateHandler::class);
        $this->dataFieldUpdaterMock = $this->createMock(AbandonedCartUpdater::class);
        $this->dataFieldUpdaterFactoryMock = $this->createMock(AbandonedCartUpdaterFactory::class);
        $this->ddgQuoteFactoryMock = $this->createMock(DotdigitalQuoteFactory::class);
        $this->quoteFactoryMock = $this->createMock(QuoteFactory::class);

        $this->abandonedCart = new AbandonedCart(
            $this->helperMock,
            $this->loggerMock,
            $this->automationResourceMock,
            $this->dataFieldUpdateHandlerMock,
            $this->dataFieldUpdaterFactoryMock,
            $this->ddgQuoteFactoryMock,
            $this->quoteFactoryMock
        );
    }

    public function testThatWeCompleteProcessLoopIfQuoteHasItems()
    {
        $contact = $this->getSubscribedContact();
        $quoteModelMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteItemModelMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $ddgQuoteModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Sales\Quote::class);

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$quoteItemModelMock]);

        $this->automationResourceMock->expects($this->never())
            ->method('setStatusAndSaveAutomation');

        $this->dataFieldUpdaterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->dataFieldUpdaterMock);

        $this->dataFieldUpdaterMock->expects($this->once())
            ->method('setDatafields')
            ->willReturn($this->dataFieldUpdaterMock);

        $this->dataFieldUpdaterMock->expects($this->once())
            ->method('updateDatafields');

        $this->ddgQuoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($ddgQuoteModelMock);

        $ddgQuoteModelMock->expects($this->once())
            ->method('getMostExpensiveItem');

        $this->abandonedCart->process($this->getAutomationCollectionMock());
    }

    public function testThatWeExitProcessLoopIfQuoteHasNoItems()
    {
        $contact = $this->getSubscribedContact();
        $automationModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Automation::class);
        $quoteModelMock = $this->createMock(\Magento\Quote\Model\Quote::class);

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([]);

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation')
            ->with(
                $automationModelMock,
                StatusInterface::CANCELLED
            );

        $this->dataFieldUpdaterMock->expects($this->never())
            ->method('setDatafields');

        $this->abandonedCart->process($this->getAutomationCollectionMock());
    }

    private function getSubscribedContact()
    {
        $contact = [
            'id' => 1,
            'status' => StatusInterface::SUBSCRIBED
        ];

        return (object) $contact;
    }

    /**
     * Use ObjectManager to give us an iterable AutomationCollection.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getAutomationCollectionMock()
    {
        $objectManager = new ObjectManager($this);
        $automationModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Automation::class);

        return $objectManager->getCollectionMock(
            AutomationCollection::class,
            [$automationModelMock]
        );
    }
}
