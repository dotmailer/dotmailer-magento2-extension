<?php

namespace Dotdigitalgroup\Email\Test\Unit\Observer\Catalog;

use Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk;
use Dotdigitalgroup\Email\Observer\Catalog\ReimportBunch;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class ReimportBunchTest extends TestCase
{
    /**
     * @var UpdateCatalogBulk
     */
    private $bulkUpdater;

    /**
     * @var Observer
     */
    private $observerMock;

    /**
     * @var ReimportBunch
     */
    private $reimportBunch;

    protected function setUp() :void
    {
        $this->bulkUpdater = $this->createMock(UpdateCatalogBulk::class);
        $this->observerMock = $this->createMock(Observer::class);

        $this->reimportBunch = new ReimportBunch(
            $this->bulkUpdater
        );
    }

    public function testThatMethodGetBunchAndExecuteAreCalling()
    {
        $this->observerMock->expects($this->once())
            ->method('__call')
            ->with('getBunch')
            ->willReturn($this->observerMock);

        $this->bulkUpdater->expects($this->once())
            ->method('execute')
            ->with($this->observerMock);

        $this->reimportBunch->execute($this->observerMock);
    }
}
