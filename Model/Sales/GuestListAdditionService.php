<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sales;

/**
 * Request-scoped record of guest orders that were just placed.
 *
 * The order placed event fires before the order is committed and the save commit event fires on every
 * order save, so this lets the commit observer act once, only for the save that places the order.
 */
class GuestListAdditionService
{
    /**
     * @var array
     */
    private $placedOrders = [];

    /**
     * @var array
     */
    private $automationQueuedOrders = [];

    /**
     * Mark a guest order as just placed.
     *
     * @param string $incrementId
     *
     * @return void
     */
    public function markPlaced(string $incrementId): void
    {
        $this->placedOrders[$incrementId] = true;
    }

    /**
     * Record that an enrolment automation was queued for the order when it was placed.
     *
     * @param string $incrementId
     *
     * @return void
     */
    public function markAutomationQueued(string $incrementId): void
    {
        $this->automationQueuedOrders[$incrementId] = true;
    }

    /**
     * Whether an enrolment automation was queued for the order when it was placed.
     *
     * Call before consumePlaced, which clears the record.
     *
     * @param string $incrementId
     *
     * @return bool
     */
    public function isAutomationQueued(string $incrementId): bool
    {
        return isset($this->automationQueuedOrders[$incrementId]);
    }

    /**
     * Whether the order was just placed. True only the first time it is asked.
     *
     * @param string $incrementId
     *
     * @return bool
     */
    public function consumePlaced(string $incrementId): bool
    {
        if (!isset($this->placedOrders[$incrementId])) {
            return false;
        }

        unset($this->placedOrders[$incrementId], $this->automationQueuedOrders[$incrementId]);

        return true;
    }
}
