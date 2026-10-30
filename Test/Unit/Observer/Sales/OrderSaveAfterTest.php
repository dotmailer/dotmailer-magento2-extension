<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Observer\Sales;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Automation as EmailAutomation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Order as EmailOrder;
use Dotdigitalgroup\Email\Model\OrderFactory as EmailOrderFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\SubscriptionDataFactory;
use Dotdigitalgroup\Email\Model\Queue\Sales\GuestListAdditionPublisher;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection as AutomationCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory as AutomationCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order as EmailOrderResource;
use Dotdigitalgroup\Email\Model\Sales\GuestListAdditionService;
use Dotdigitalgroup\Email\Observer\Sales\OrderSaveAfter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class OrderSaveAfterTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var GuestListAdditionPublisher|MockObject
     */
    private $guestListPublisherMock;

    /**
     * @var GuestListAdditionService
     */
    private $guestListAdditionService;

    /**
     * @var OrderSaveAfter
     */
    private $observer;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var AutomationPublisher|MockObject
     */
    private $automationPublisherMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isGuestSyncEnabled')->willReturn(true);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->guestListPublisherMock = $this->createMock(GuestListAdditionPublisher::class);
        $this->guestListAdditionService = new GuestListAdditionService();

        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(1);
        $store->method('getId')->willReturn(2);
        $store->method('getName')->willReturn('Store 2');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $emailOrder = new class extends EmailOrder {
            public function __construct()
            {
            }

            public function loadOrCreateOrder($orderId, $quoteId)
            {
                return $this;
            }

            public function __call($method, $args)
            {
                return $this;
            }
        };
        $emailOrderFactory = $this->createMock(EmailOrderFactory::class);
        $emailOrderFactory->method('create')->willReturn($emailOrder);

        $contactCollection = $this->createMock(ContactCollection::class);
        $contact = new class extends Contact {
            public function __construct()
            {
                $this->setData(['id' => 10, 'customer_id' => 0, 'is_subscriber' => 0]);
            }
        };
        $contactCollection->method('loadByCustomerEmail')->willReturn($contact);
        $contactCollectionFactory = $this->createMock(ContactCollectionFactory::class);
        $contactCollectionFactory->method('create')->willReturn($contactCollection);

        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->automationPublisherMock = $this->createMock(AutomationPublisher::class);
        $existingAutomations = $this->createMock(AutomationCollection::class);
        $existingAutomations->method('addFieldToFilter')->willReturnSelf();
        $existingAutomations->method('setPageSize')->willReturnSelf();
        $existingAutomations->method('getSize')->willReturn(0);
        $automationCollectionFactory = $this->createMock(AutomationCollectionFactory::class);
        $automationCollectionFactory->method('create')->willReturn($existingAutomations);
        $automationFactory = $this->createMock(AutomationFactory::class);
        $automationFactory->method('create')->willReturn(new class extends EmailAutomation {
            public function __construct()
            {
            }

            public function __call($method, $args)
            {
                return $this;
            }
        });

        $this->observer = new OrderSaveAfter(
            $this->loggerMock,
            $automationFactory,
            $this->createMock(Automation::class),
            $this->createMock(EmailOrderResource::class),
            $emailOrderFactory,
            $this->serializerMock,
            $this->scopeConfigMock,
            $storeManager,
            $this->createMock(OrderCollectionFactory::class),
            $this->helperMock,
            $this->createMock(ContactResource::class),
            $contactCollectionFactory,
            $automationCollectionFactory,
            $this->createMock(SubscriptionDataFactory::class),
            $this->automationPublisherMock,
            $this->createMock(PublisherInterface::class),
            $this->guestListPublisherMock,
            $this->guestListAdditionService
        );
    }

    public function testPlacedGuestOrderPublishesListAdditionWithOrderStore()
    {
        $this->helperMock->method('getGuestListByStoreId')->with(2)->willReturn('222');
        $this->guestListAdditionService->markPlaced('000000042');
        $this->guestListPublisherMock->expects($this->once())
            ->method('publish')
            ->with(1, 2, 10, 'guest@example.com', '000000042');

        $this->observer->execute($this->event());
    }

    public function testLaterSavesOfTheSameOrderPublishNothing()
    {
        $this->helperMock->method('getGuestListByStoreId')->willReturn('222');
        $this->guestListAdditionService->markPlaced('000000042');
        $this->guestListPublisherMock->expects($this->once())->method('publish');

        $this->observer->execute($this->event());
        $this->observer->execute($this->event());
    }

    public function testOrderThatWasNotJustPlacedPublishesNothing()
    {
        $this->helperMock->method('getGuestListByStoreId')->willReturn('222');
        $this->guestListPublisherMock->expects($this->never())->method('publish');

        $this->observer->execute($this->event());
    }

    public function testListAdditionIsSkippedWhenAnAutomationEnrolmentWasQueued()
    {
        $this->helperMock->method('getGuestListByStoreId')->willReturn('222');
        $this->scopeConfigMock->method('getValue')->willReturn('serialized');
        $this->serializerMock->method('unserialize')
            ->willReturn([['status' => 'pending', 'automation' => '55']]);
        $this->guestListAdditionService->markPlaced('000000042');
        $this->automationPublisherMock->expects($this->once())->method('publish');
        $this->guestListPublisherMock->expects($this->never())->method('publish');

        $this->observer->execute($this->event());
    }

    public function testListAdditionIsSkippedWhenAGuestOrderAutomationWasQueuedOnPlacement()
    {
        $this->helperMock->method('getGuestListByStoreId')->willReturn('222');
        $this->guestListAdditionService->markPlaced('000000042');
        $this->guestListAdditionService->markAutomationQueued('000000042');
        $this->guestListPublisherMock->expects($this->never())->method('publish');

        $this->observer->execute($this->event());
    }

    public function testNothingIsPublishedWhenNoGuestListIsMappedForTheStore()
    {
        $this->helperMock->method('getGuestListByStoreId')->willReturn('');
        $this->guestListAdditionService->markPlaced('000000042');
        $this->guestListPublisherMock->expects($this->never())->method('publish');

        $this->observer->execute($this->event());
    }

    public function testPublishFailureIsLoggedAndDoesNotBreakTheOrderSave()
    {
        $this->helperMock->method('getGuestListByStoreId')->willReturn('222');
        $this->guestListAdditionService->markPlaced('000000042');
        $this->guestListPublisherMock->method('publish')
            ->willThrowException(new \RuntimeException('queue down'));
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Guest list addition publish failed');

        $this->observer->execute($this->event());
    }

    private function event(): Observer
    {
        $order = new DataObject([
            'entity_id' => 99,
            'quote_id' => 5,
            'store_id' => 2,
            'status' => 'pending',
            'customer_email' => 'guest@example.com',
            'customer_is_guest' => 1,
            'increment_id' => '000000042',
        ]);

        return new Observer(['event' => new Event(['order' => $order])]);
    }
}
