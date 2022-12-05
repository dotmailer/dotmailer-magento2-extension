<?php

namespace Dotdigitalgroup\Email\Test\Unit\Traits;

use Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection as AutomationCollection;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

trait AutomationProcessorTrait
{
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

    private function setupAutomationModel()
    {
        $this->automationModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->automationModelMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn('1');
    }

    private function setupContactModel()
    {
        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('loadByCustomerEmail')
            ->willReturn($this->contactModelMock);
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
}
