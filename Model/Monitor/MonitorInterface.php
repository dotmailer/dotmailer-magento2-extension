<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

interface MonitorInterface
{
    /**
     * Run this monitor
     * @return void
     */
    public function run();
}
