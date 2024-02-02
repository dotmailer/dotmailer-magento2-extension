<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data as CartInsight;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Enroller;
use Dotdigitalgroup\Email\Model\AbandonedCart\Interval;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Rules;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Saver;
use Dotdigitalgroup\Email\Model\AbandonedCart\TimeLimit;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Contact\Patcher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory as AutomationCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProgramEnrolmentEnrollerTest extends TestCase
{
    /**
     * @var CollectionFactory|MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var Collection|MockObject
     */
    private $orderCollectionMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Interval
     */
    private $interval;

    /**
     * @var Saver
     */
    private $saver;

    /**
     * @var Rules
     */
    private $rules;

    /**
     * @var Enroller
     */
    private $model;

    /**
     * @var CartInsight
     */
    private $cartInsight;

    /**
     * @var MockObject
     */
    private $automationCollectionFactoryMock;

    /**
     * @var MockObject
     */
    private $timeLimitMock;

    /**
     * @var MockObject
     */
    private $quoteMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Patcher|MockObject
     */
    private $patcherMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    protected function setUp() :void
    {
        $this->orderCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->interval = $this->createMock(Interval::class);
        $this->saver = $this->createMock(Saver::class);
        $this->rules = $this->createMock(Rules::class);
        $this->cartInsight = $this->createMock(CartInsight::class);
        $this->patcherMock = $this->createMock(Patcher::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->onlyMethods(['hasItems'])
            ->addMethods(['getCustomerEmail'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCollectionMock = $this->createMock(Collection::class);
        $this->orderCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->quoteMock]));

        $this->automationCollectionFactoryMock = $this->getMockBuilder(AutomationCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getAbandonedCartAutomationsForContactByInterval','getSize'])
            ->getMock();

        $this->timeLimitMock = $this->createMock(TimeLimit::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->prepare();

        $this->model = new Enroller(
            $this->loggerMock,
            $this->orderCollectionFactoryMock,
            $this->interval,
            $this->patcherMock,
            $this->saver,
            $this->rules,
            $this->cartInsight,
            $this->automationCollectionFactoryMock,
            $this->timeLimitMock,
            $this->scopeConfigMock,
            $this->storeManagerMock
        );
    }

    public function testAutomationIsNotSavedIfQuoteHasNoItems()
    {
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('hasItems')
            ->willReturn(false);

        $this->saver->expects($this->never())
            ->method('save');

        $this->model->process();
    }

    public function testAutomationIsSavedIfQuoteHasItemsAndNoAbandonedCartLimit()
    {
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('hasItems')
            ->willReturn(true);

        $this->timeLimitMock->expects($this->atLeastOnce())
            ->method('getAbandonedCartTimeLimit')
            ->willReturn(null);

        $this->saver->expects($this->atLeastOnce())
            ->method('save');

        $this->model->process();
    }

    public function testAutomationIsSavedIfQuoteHasItemsAndAbandonedCartLimitPasses()
    {
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('hasItems')
            ->willReturn(true);

        $this->timeLimitMock->expects($this->atLeastOnce())
            ->method('getAbandonedCartTimeLimit')
            ->willReturn('6');

        $this->automationCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->automationCollectionFactoryMock);

        $this->automationCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('getAbandonedCartAutomationsForContactByInterval')
            ->willReturn($this->automationCollectionFactoryMock);

        $this->automationCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(0);

        $this->saver->expects($this->atLeastOnce())
            ->method('save');

        $this->model->process();
    }

    public function testAutomationIsNotSavedIfQuoteHasItemsAndAbandonedCartLimitFails()
    {
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('hasItems')
            ->willReturn(true);

        $this->timeLimitMock->expects($this->atLeastOnce())
            ->method('getAbandonedCartTimeLimit')
            ->willReturn('6');

        $this->automationCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->automationCollectionFactoryMock);

        $this->automationCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('getAbandonedCartAutomationsForContactByInterval')
            ->willReturn($this->automationCollectionFactoryMock);

        $this->automationCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(1);

        $this->saver->expects($this->never())
            ->method('save');

        $this->model->process();
    }

    private function prepare()
    {
        $storeId = 1;
        $programId = "123456";
        $updated = [
            'from' => '2019-01-01 12:00:00',
            'to' => '2019-01-01 16:00:00',
            'date' => true
        ];

        // foreach ($this->helper->getStores() as $store) expects an array,
        // but it need only contain one mock for simplicity
        $storesArray = [
            $this->createMock(\Magento\Store\Model\Store::class)
        ];

        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->willReturn($storesArray);

        $storesArray[0]->method('getId')
            ->willReturn($storeId);

        $this->scopeConfigMock->expects($this->atLeastOnce())
            ->method('getValue')
            ->withConsecutive(
                [Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_ID,
                ScopeInterface::SCOPE_STORE,
                $storeId],
                [Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
                    ScopeInterface::SCOPE_STORE]
            )
            ->willReturnOnConsecutiveCalls(
                $programId,
                500
            );

        // Enrolment interval
        $this->interval
            ->method('getAbandonedCartProgramEnrolmentWindow')
            ->with($storeId)
            ->willReturn($updated);

        $this->orderCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->atLeastOnce())
            ->method('getStoreQuotesForAutomationEnrollmentGuestsAndCustomers')
            ->with(
                $storeId,
                $updated
            )
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(1500);

        $this->orderCollectionMock->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->atLeastOnce())
            ->method('setCurPage')
            ->willReturn($this->orderCollectionMock);

        $this->quoteMock->method('getCustomerEmail')->willReturn('chaz@emailsim.io');

        $this->patcherMock->expects($this->atLeastOnce())
            ->method('getOrCreateContactByEmail');

        $this->cartInsight->expects($this->atLeastOnce())
            ->method('send');

        $this->rules
            ->method('apply')
            ->with($this->orderCollectionMock, $storeId);
    }
}
