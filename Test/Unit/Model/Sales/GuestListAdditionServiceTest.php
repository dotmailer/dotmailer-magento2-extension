<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sales;

use Dotdigitalgroup\Email\Model\Sales\GuestListAdditionService;
use PHPUnit\Framework\TestCase;

class GuestListAdditionServiceTest extends TestCase
{
    public function testPlacedOrderIsConsumedOnlyOnce()
    {
        $service = new GuestListAdditionService();
        $service->markPlaced('000000042');

        $this->assertTrue($service->consumePlaced('000000042'));
        $this->assertFalse($service->consumePlaced('000000042'));
    }

    public function testUnmarkedOrderIsNotConsumed()
    {
        $service = new GuestListAdditionService();
        $service->markPlaced('000000042');

        $this->assertFalse($service->consumePlaced('000000043'));
    }

    public function testAutomationQueuedIsRememberedUntilOrderIsConsumed()
    {
        $service = new GuestListAdditionService();
        $service->markPlaced('000000042');
        $service->markAutomationQueued('000000042');

        $this->assertTrue($service->isAutomationQueued('000000042'));
        $this->assertFalse($service->isAutomationQueued('000000043'));

        $service->consumePlaced('000000042');
        $this->assertFalse($service->isAutomationQueued('000000042'));
    }
}
