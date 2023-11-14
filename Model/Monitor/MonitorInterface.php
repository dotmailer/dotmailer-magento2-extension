<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

interface MonitorInterface
{
    /**
     * Fetch errors inside the time window.
     *
     * @param array $timeWindow
     * @return void
     */
    public function fetchErrors(array $timeWindow);

    /**
     * Get the flag code for the monitor error flag.
     *
     * @param array $items
     * @return array
     */
    public function filterErrorItems(array $items);
}
