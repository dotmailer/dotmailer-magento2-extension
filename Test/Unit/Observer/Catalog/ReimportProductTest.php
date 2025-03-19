<?php

namespace Dotdigitalgroup\Email\Test\Unit\Observer\Catalog;

use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalog;
use Dotdigitalgroup\Email\Observer\Catalog\ReimportProduct;
use Dotdigitalgroup\Email\Model\Catalog\CatalogService;
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
     * @var CatalogService
     */
    private $catalogServiceMock;

    /**
     * @var ReimportProduct
     */
    private $reimportProduct;

    protected function setUp() :void
    {
        $this->updaterMock = $this->createMock(UpdateCatalog::class);
        $this->catalogServiceMock = $this->createMock(CatalogService::class);
        $this->observerMock = $this->createMock(Observer::class);

        $this->reimportProduct = new ReimportProduct(
            $this->updaterMock,
            $this->catalogServiceMock
        );
    }

    public function testThatGetEventGetDataObjectGetIdAndExecuteMethodsExecuted()
    {
        $eventMock = $this->createMock(\Magento\Framework\Event::class);
        $this->observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $productInterfaceMock = $this->createMock(\Magento\Catalog\Api\Data\ProductInterface::class);
        $eventMock->expects($this->once())
            ->method('__call')
            ->with('getProduct')
            ->willReturn($productInterfaceMock);

        $this->updaterMock->expects($this->once())
            ->method('execute')
            ->with($productInterfaceMock);

        $this->reimportProduct->execute($this->observerMock);
    }
}
