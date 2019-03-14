<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Saver;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use PHPUnit\Framework\TestCase;

class ProgramEnrolmentSaverTest extends TestCase
{
    /**
     * @var AutomationFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationFactoryMock;

    /**
     * @var Automation|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationResourceMock;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataHelperMock;

    /**
     * @var Saver
     */
    private $model;

    protected function setUp()
    {
        $this->automationFactoryMock = $this->getMockBuilder(AutomationFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->automationResourceMock = $this->getMockBuilder(Automation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Saver(
            $this->automationFactoryMock,
            $this->automationResourceMock,
            $this->dataHelperMock
        );
    }

    public function testAutomationWasSaved()
    {
        // Quote
        $quoteModel = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteModel->expects($this->once())
            ->method('getCustomerEmail');

        // Store
        $storeModel = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeModel->expects($this->once())
            ->method('getWebsiteId');

        // Automation
        $automationModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Automation::class);

        $automationModelMock->expects($this->atLeastOnce())
            ->method('__call')
            ->withConsecutive(
                [$this->equalTo('setEmail')],
                [$this->equalTo('setAutomationType')],
                [$this->equalTo('setEnrolmentStatus')],
                [$this->equalTo('setTypeId')],
                [$this->equalTo('setWebsiteId')],
                [$this->equalTo('setStoreName')],
                [$this->equalTo('setProgramId')]
            )
            ->willReturnOnConsecutiveCalls(
                $automationModelMock,
                $automationModelMock,
                $automationModelMock,
                $automationModelMock,
                $automationModelMock,
                $automationModelMock,
                $automationModelMock,
                $automationModelMock
            );

        $this->automationFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($automationModelMock);

        $this->automationResourceMock->expects($this->once())
            ->method('save')
            ->with($automationModelMock)
            ->willReturn($automationModelMock);

        // Arbitrary ID for dotdigital program
        $programId = "123456";

        $this->model->save($quoteModel, $storeModel, $programId);
    }
}
