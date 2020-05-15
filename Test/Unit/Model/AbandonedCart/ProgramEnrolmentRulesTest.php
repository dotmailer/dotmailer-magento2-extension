<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\ProgramEnrolment;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\AbandonedCart\ProgramEnrolment\Rules;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Dotdigitalgroup\Email\Model\RulesFactory;
use PHPUnit\Framework\TestCase;

class ProgramEnrolmentRulesTest extends TestCase
{
    /**
     * @var RulesFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rulesFactoryMock;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataHelperMock;

    /**
     * @var OrderCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var Rules
     */
    private $model;

    protected function setUp() :void
    {
        $this->rulesFactoryMock = $this->getMockBuilder(RulesFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Rules(
            $this->rulesFactoryMock,
            $this->dataHelperMock
        );
    }

    public function testRulesAreAppliedToCollection()
    {
        $storeId = 1;
        $websiteId = 1;

        $rulesModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Rules::class);
        $this->rulesFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rulesModelMock);

        $storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $storeModelMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->dataHelperMock->storeManager = $storeManagerMock;

        $storeManagerMock->method("getStore")
            ->with($storeId)
            ->willReturn($storeModelMock);

        $storeModelMock->method("getWebsiteId")
            ->willReturn($websiteId);

        $rulesModelMock->method("process")
            ->with(
                $this->orderCollectionFactoryMock,
                \Dotdigitalgroup\Email\Model\Rules::ABANDONED,
                $websiteId
            )
            ->willReturn($this->orderCollectionFactoryMock);

        $this->model->apply($this->orderCollectionFactoryMock, $storeId);
    }
}
