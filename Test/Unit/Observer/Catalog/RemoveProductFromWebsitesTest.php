<?php

namespace Dotdigitalgroup\Email\Test\Unit\Observer\Catalog;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Catalog\StoreLevelCatalogDeleter;
use Dotdigitalgroup\Email\Observer\Catalog\RemoveProductFromWebsites;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RemoveProductFromWebsitesTest extends TestCase
{
    private function runObserver(?array $original, array $current, $expectation): void
    {
        $deleter = $this->createMock(StoreLevelCatalogDeleter::class);
        $expectation($deleter);

        $product = $this->createMock(Product::class);
        $product->method('getOrigData')->with('website_ids')->willReturn($original);
        $product->method('getWebsiteIds')->willReturn($current);
        $product->method('getId')->willReturn(7);

        $event = $this->createMock(Event::class);
        $event->method('__call')->with('getProduct')->willReturn($product);
        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        (new RemoveProductFromWebsites($deleter, $this->createMock(Logger::class)))->execute($observer);
    }

    public function testDeletesForRemovedWebsitesOnly()
    {
        $this->runObserver([1, 2, 3], [1], function ($deleter) {
            $deleter->expects($this->once())->method('execute')->with(7, [2, 3]);
        });
    }

    public function testNoDeleteWhenNothingRemoved()
    {
        $this->runObserver([1], [1, 2], function ($deleter) {
            $deleter->expects($this->never())->method('execute');
        });
    }

    public function testNoDeleteForNewProduct()
    {
        $this->runObserver(null, [1], function ($deleter) {
            $deleter->expects($this->never())->method('execute');
        });
    }
}
