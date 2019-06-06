<?php

namespace Dotdigitalgroup\Email\Test\Unit\Observer\Catalog;
use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog;
use Dotdigitalgroup\Email\Observer\Catalog\ReimportProduct;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class ReimportProductTest extends TestCase
{
    /**
     * @var UpdateCatalog
     */
    private $updaterMock;

    /**
     * @var Observer
     */
    private $observerMock;

    /**
     * @var ReimportProduct
     */
    private $reimportProduct;

    protected function setUp()
    {
        $this->updaterMock = $this->createMock(UpdateCatalog::class);
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->setMethods(['getEvent','getDataObject','getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->reimportProduct = new ReimportProduct(
            $this->updaterMock
        );
    }

    public function testThatGetEventGetDataObjectGetIdAndExecuteMethodsExecuted()
    {
        $this->observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->observerMock);

        $this->observerMock->expects($this->once())
            ->method('getDataObject')
            ->willReturn($this->observerMock);

        $this->observerMock->expects($this->once())
            ->method('getId')
            ->willReturn($this->observerMock);

        $this->updaterMock->expects($this->once())
            ->method('execute')
            ->with($this->observerMock);

        $this->reimportProduct->execute($this->observerMock);
    }
}