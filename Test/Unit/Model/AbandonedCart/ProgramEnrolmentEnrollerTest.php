<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Enroller;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Interval;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Saver;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Rules;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data as CartInsight;
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

    protected function setUp()
    {
        $this->orderCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
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

        //We need to mock an Object to behave as a Collection in order to pass the tests
        $this->objectManager = new ObjectManager($this);
        $this->orderCollectionMock = $this->objectManager->getCollectionMock(Collection::class, [$this->orderCollectionMock]);

        $this->model = new Enroller(
            $this->orderCollectionFactoryMock,
            $this->dataHelperMock,
            $this->interval,
            $this->saver,
            $this->rules,
            $this->cartInsight
        );
    }

    public function testAutomation_IsSaved_IfACProgramIdSet()
    {
        $storeId = 1;
        $programId = "123456";
        $updated = array(
            'from' => '2019-01-01 12:00:00',
            'to' => '2019-01-01 16:00:00',
            'date' => true
        );

        // foreach ($this->helper->getStores() as $store) expects an array, but it need only contain one mock for simplicity
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
            ->method('getStoreQuotesForGuestsAndCustomers')
            ->with(
                $storeId,
                $updated
            )
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock ->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn(1500);

        $this->orderCollectionMock->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->atLeastOnce())
            ->method('setCurPage')
            ->willReturn($this->orderCollectionMock);

        $this->saver->expects($this->atLeastOnce())
            ->method('save');

        $this->cartInsight->expects($this->atLeastOnce())
            ->method('send');

        $this->rules
            ->method('apply')
            ->with($this->orderCollectionMock , $storeId);

        $this->model->process();
    }
}
