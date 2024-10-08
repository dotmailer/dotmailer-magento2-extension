<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Saver;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProgramEnrolmentSaverTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var AutomationFactory|MockObject
     */
    private $automationFactoryMock;

    /**
     * @var AutomationPublisher|MockObject
     */
    private $automationPublisherMock;

    /**
     * @var AutomationResource|MockObject
     */
    private $automationResourceMock;

    /**
     * @var Saver
     */
    private $model;

    protected function setUp() :void
    {
        $this->automationFactoryMock = $this->createMock(AutomationFactory::class);
        $this->automationPublisherMock = $this->createMock(AutomationPublisher::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->model = new Saver(
            $this->loggerMock,
            $this->automationFactoryMock,
            $this->automationPublisherMock,
            $this->automationResourceMock
        );
    }

    public function testAutomationWasSaved()
    {
        $automationModelMock = $this->createMock(Automation::class);
        $this->automationFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($automationModelMock);

        $quoteModel = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteModel->expects($this->once())
            ->method('getCustomerEmail');

        $storeModel = $this->createMock(Store::class);

        $storeModel->expects($this->once())
            ->method('getId');

        $storeModel->expects($this->once())
            ->method('getWebsiteId');

        $storeModel->expects($this->once())
            ->method('getName');

        $matcher = $this->exactly(8);
        $automationModelMock
            ->expects($matcher)
            ->method('__call')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->numberOfInvocations()) {
                    1 => 'setEmail',
                    2 => 'setAutomationType',
                    3 => 'setEnrolmentStatus',
                    4 => 'setTypeId',
                    5 => 'setWebsiteId',
                    6 => 'setStoreId',
                    7 => 'setStoreName',
                    8 => 'setProgramId',
                };
            })
            ->willReturn(
                $automationModelMock
            );

        $this->automationResourceMock->expects($this->once())
            ->method('save')
            ->with($automationModelMock)
            ->willReturn($automationModelMock);

        $this->automationPublisherMock->expects($this->once())
            ->method('publish')
            ->with($automationModelMock);

        // Arbitrary ID for Dotdigital program
        $programId = "123456";

        $this->model->save($quoteModel, $storeModel, $programId);
    }
}
