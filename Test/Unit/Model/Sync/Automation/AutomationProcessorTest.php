<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection as AutomationCollection;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class AutomationProcessorTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var AutomationResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationResourceMock;

    /**
     * @var DataFieldUpdateHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdateHandlerMock;

    /**
     * @var AutomationProcessor
     */
    private $automationProcessor;

    private $automationModelMock;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->dataFieldUpdateHandlerMock = $this->createMock(DataFieldUpdateHandler::class);
        $this->automationModelMock = $this->getMockBuilder(\Dotdigitalgroup\Email\Model\Automation::class)
            ->addMethods(['getAutomationType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->automationProcessor = new AutomationProcessor(
            $this->helperMock,
            $this->automationResourceMock,
            $this->dataFieldUpdateHandlerMock
        );
    }

    public function testSubscribedContactTriggersDataFieldUpdate()
    {
        $contact = $this->getSubscribedContact();

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->automationResourceMock->expects($this->never())
            ->method('setStatusAndSaveAutomation');

        $this->automationModelMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn('customer_automation');

        $this->dataFieldUpdateHandlerMock->expects($this->once())
            ->method('updateDatafieldsByType');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testPendingOptInContactIsSavedNotProcessed()
    {
        $contact = $this->getPendingOptInContact();

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation');

        $this->dataFieldUpdateHandlerMock->expects($this->never())
            ->method('updateDatafieldsByType');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testRowIsMarkedAsFailedIfContactNotFound()
    {
        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn(false);

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation')
            ->with(
                $this->automationModelMock,
                StatusInterface::FAILED,
                'Contact cannot be created or has been suppressed'
            );

        $this->dataFieldUpdateHandlerMock->expects($this->never())
            ->method('updateDatafieldsByType');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    private function getSubscribedContact()
    {
        $contact = [
            'id' => 1,
            'status' => StatusInterface::SUBSCRIBED
        ];

        return (object) $contact;
    }

    private function getPendingOptInContact()
    {
        $contact = [
            'id' => 1,
            'status' => StatusInterface::PENDING_OPT_IN
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

        return $objectManager->getCollectionMock(
            AutomationCollection::class,
            [$this->automationModelMock]
        );
    }
}
