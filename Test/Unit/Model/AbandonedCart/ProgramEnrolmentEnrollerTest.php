<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Enroller;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Interval;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Saver;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Rules;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection;
use Dotdigitalgroup\Email\Helper\Data;
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

    protected function setUp()
    {
        $this->orderCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCollectionMock = $this->createMock(Collection::class);
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigModelMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $this->interval = $this->createMock(Interval::class);
        $this->saver = $this->createMock(Saver::class);
        $this->rules = $this->createMock(Rules::class);

        $this->model = new Enroller(
            $this->orderCollectionFactoryMock,
            $this->dataHelperMock,
            $this->interval,
            $this->saver,
            $this->rules
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

        $this->scopeConfigModelMock->expects($this->atLeastOnce())
            ->method('getValue')
            ->with(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_LOSTBASKET_ENROL_TO_PROGRAM_ID,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($programId);

        // Enrolment interval
        $this->interval
            ->method('getAbandonedCartProgramEnrolmentWindow')
            ->with($storeId)
            ->willReturn($updated);

        $this->orderCollectionFactoryMock
            ->method('create')
            ->willReturn($this->orderCollectionMock);

        $quotesArray = [
            $this->createMock(\Magento\Quote\Model\Quote::class)
        ];

        $this->orderCollectionMock->expects($this->once())
            ->method('getStoreQuotesForGuestsAndCustomers')
            ->with(
                $storeId,
                $updated
            )
            ->willReturn($quotesArray);

        $this->rules
            ->method('apply')
            ->with($quotesArray, $storeId);

        $this->model->process();
    }
}
