<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\Monitor\Cron\MonitorFactory as CronMonitor;

class Monitor
{
    /**
     * @var CronMonitor
     */
    private $cronMonitor;

    /**
     * Monitor constructor.
     * @param CronMonitor $cronMonitor
     */
    public function __construct(
        CronMonitor $cronMonitor
    ) {
        $this->cronMonitor = $cronMonitor;
    }

    /**
     * @return void
     */
    public function runAll()
    {
        $this->cronMonitor->create()
            ->run();
    }
}
