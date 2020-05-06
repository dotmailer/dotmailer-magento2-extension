<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

interface MonitorInterface
{
    /**
     * Fetch errors inside the time window
     * @param array $timeWindow
     * @return void
     */
    public function fetchErrors(array $timeWindow);

    /**
     * @param array $items
     * @return array
     */
    public function filterErrorItems(array $items);
}
