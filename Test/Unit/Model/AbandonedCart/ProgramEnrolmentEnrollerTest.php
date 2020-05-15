<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data as CartInsight;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Enroller;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Interval;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Rules;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Saver;
use Dotdigitalgroup\Email\Model\AbandonedCart\TimeLimit;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory as AutomationCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ProgramEnrolmentEnrollerTest extends TestCase
{
    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCollectionMock;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataHelperMock;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigModelMock;

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
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $automationCollectionFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $timeLimitMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteMock;

    protected function setUp() :void
    {
        $this->orderCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigModelMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->interval = $this->createMock(Interval::class);
        $this->saver = $this->createMock(Saver::class);
        $this->rules = $this->createMock(Rules::class);
        $this->cartInsight = $this->createMock(CartInsight::class);

        //We need to mock an Object to behave as a Collection (of Quote objects) in order to pass the tests
        $this->objectManager = new ObjectManager($this);
        $this->quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->orderCollectionMock = $this->objectManager->getCollectionMock(Collection::class, [$this->quoteMock]);

        $this->automationCollectionFactoryMock = $this->getMockBuilder(AutomationCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getAbandonedCartAutomationsForContactByInterval','getSize'])
            ->getMock();

        $this->timeLimitMock = $this->createMock(TimeLimit::class);

        $this->prepare();

        $this->model = new Enroller(
            $this->orderCollectionFactoryMock,
            $this->dataHelperMock,
            $this->interval,
            $this->saver,
            $this->rules,
            $this->cartInsight,
            $this->automationCollectionFactoryMock,
            $this->timeLimitMock
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

        $this->dataHelperMock->expects($this->once())
            ->method('getStores')
            ->willReturn($storesArray);

        $storesArray[0]->method('getId')
            ->willReturn($storeId);

        // Scope config
        $this->dataHelperMock
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigModelMock);

        $this->scopeConfigModelMock->expects($this->at(0))
            ->method('getValue')
            ->with(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_ID,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($programId);

        $this->scopeConfigModelMock->expects($this->at(1))
            ->method('getValue')
            ->with(
                Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->willReturn($batchSize = 500);

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

        $this->dataHelperMock->expects($this->atLeastOnce())
            ->method('getOrCreateContact')
            ->willReturn(true);

        $this->cartInsight->expects($this->atLeastOnce())
            ->method('send');

        $this->rules
            ->method('apply')
            ->with($this->orderCollectionMock, $storeId);
    }
}
