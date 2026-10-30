<?php

namespace Dotdigitalgroup\Email\Test\Unit\Plugin;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Catalog\StoreLevelCatalogDeleter;
use Dotdigitalgroup\Email\Plugin\ProductWebsiteUnassignPlugin;
use Magento\Catalog\Model\Product\Action;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProductWebsiteUnassignPluginTest extends TestCase
{
    public function testQueuesDeletesOnRemove()
    {
        $deleter = $this->createMock(StoreLevelCatalogDeleter::class);
        $deleter->expects($this->exactly(2))->method('execute')
            ->willReturnCallback(function ($id, $websites) {
                $this->assertSame([2], $websites);
            });

        $plugin = new ProductWebsiteUnassignPlugin($deleter, $this->createMock(Logger::class));
        $plugin->afterUpdateWebsites($this->createMock(Action::class), null, [1, 2, 2], [2], 'remove');
    }

    public function testIgnoresAdd()
    {
        $deleter = $this->createMock(StoreLevelCatalogDeleter::class);
        $deleter->expects($this->never())->method('execute');

        $plugin = new ProductWebsiteUnassignPlugin($deleter, $this->createMock(Logger::class));
        $plugin->afterUpdateWebsites($this->createMock(Action::class), null, [1], [2], 'add');
    }
}
