<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Saver;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use PHPUnit\Framework\TestCase;

class ProgramEnrolmentSaverTest extends TestCase
{
    /**
     * @var AutomationFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationFactoryMock;

    /**
     * @var AutomationPublisher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationPublisherMock;

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

    protected function setUp() :void
    {
        $this->automationFactoryMock = $this->createMock(AutomationFactory::class);
        $this->automationPublisherMock = $this->createMock(AutomationPublisher::class);
        $this->automationResourceMock = $this->createMock(Automation::class);
        $this->dataHelperMock = $this->createMock(Data::class);

        $this->model = new Saver(
            $this->automationFactoryMock,
            $this->automationPublisherMock,
            $this->automationResourceMock,
            $this->dataHelperMock
        );
    }

    public function testAutomationWasSaved()
    {
        // Quote
        $quoteModel = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->addMethods(['getCustomerEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteModel->expects($this->once())
            ->method('getCustomerEmail');

        // Store
        $storeModel = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeModel->expects($this->once())
            ->method('getId');

        $storeModel->expects($this->once())
            ->method('getWebsiteId');

        $storeModel->expects($this->once())
            ->method('getName');

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
                [$this->equalTo('setStoreId')],
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

        // Arbitrary ID for Dotdigital program
        $programId = "123456";

        $this->model->save($quoteModel, $storeModel, $programId);
    }
}
