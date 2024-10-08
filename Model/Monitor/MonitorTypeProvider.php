<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

use Dotdigitalgroup\Email\Model\Monitor\Cron\Monitor;

class MonitorTypeProvider
{
    /**
     * @var Cron\Monitor
     */
    private $cronMonitor;

    /**
     * @var Importer\Monitor
     */
    private $importerMonitor;

    /**
     * @var Campaign\Monitor
     */
    private $campaignMonitor;

    /**
     * @var Automation\Monitor
     */
    private $automationMonitor;

    /**
     * @var Smtp\Monitor
     */
    private $smtpMonitor;

    /**
     * @var Queue\Monitor
     */
    private $queueMonitor;

    /**
     * MonitorTypeProvider constructor.
     *
     * @param Monitor $cronMonitor
     * @param Importer\Monitor $importerMonitor
     * @param Campaign\Monitor $campaignMonitor
     * @param Automation\Monitor $automationMonitor
     * @param Smtp\Monitor $smtpMonitor
     * @param Queue\Monitor $queueMonitor
     */
    public function __construct(
        Cron\Monitor $cronMonitor,
        Importer\Monitor $importerMonitor,
        Campaign\Monitor $campaignMonitor,
        Automation\Monitor $automationMonitor,
        Smtp\Monitor $smtpMonitor,
        Queue\Monitor $queueMonitor
    ) {
        $this->cronMonitor = $cronMonitor;
        $this->importerMonitor = $importerMonitor;
        $this->campaignMonitor = $campaignMonitor;
        $this->automationMonitor = $automationMonitor;
        $this->smtpMonitor = $smtpMonitor;
        $this->queueMonitor = $queueMonitor;
    }

    /**
     * Get all types associated with this provider.
     *
     * @return array
     */
    public function getTypes()
    {
        return get_object_vars($this);
    }
}
