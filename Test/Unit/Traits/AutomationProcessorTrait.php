<?php

namespace Dotdigitalgroup\Email\Test\Unit\Traits;

use Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection as AutomationCollection;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\MockObject\Exception;

trait AutomationProcessorTrait
{
    /**
     * Get an iterable AutomationCollection.
     *
     * @return AutomationCollection|(AutomationCollection&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     * @throws Exception
     */
    private function getAutomationCollectionMock()
    {
        $automationCollectionMock = $this->createMock(AutomationCollection::class);
        $automationCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->automationModelMock]));

        return $automationCollectionMock;
    }

    private function setupAutomationModel()
    {
        $this->automationModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->automationModelMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->automationModelMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn('1');
    }

    private function setupContactModel()
    {
        $this->contactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactModelMock);

        $this->contactModelMock->expects($this->once())
            ->method('loadByCustomerEmail')
            ->willReturn($this->contactModelMock);
    }

    private function setupSubscriberModel()
    {
        $subscriberModelMock = $this->createMock(Subscriber::class);

        $this->backportedSubscriberLoaderMock->expects($this->once())
            ->method('loadBySubscriberEmail')
            ->willReturn($subscriberModelMock);
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
