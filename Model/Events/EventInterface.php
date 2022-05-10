<?php

namespace Dotdigitalgroup\Email\Model\Events;

/**
 * Event Listener of the messages.
 */
interface EventInterface
{
    /**
     * Get Updated Data.
     *
     * @return string
     */
    public function update(): string;
}
