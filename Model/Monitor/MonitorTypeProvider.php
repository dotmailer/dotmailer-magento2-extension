<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

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
     * MonitorTypeProvider constructor.
     * @param Cron\Monitor $cronMonitor
     * @param Importer\Monitor $importerMonitor
     * @param Campaign\Monitor $campaignMonitor
     * @param Automation\Monitor $automationMonitor
     */
    public function __construct(
        Cron\Monitor $cronMonitor,
        Importer\Monitor $importerMonitor,
        Campaign\Monitor $campaignMonitor,
        Automation\Monitor $automationMonitor
    ) {
        $this->cronMonitor = $cronMonitor;
        $this->importerMonitor = $importerMonitor;
        $this->campaignMonitor = $campaignMonitor;
        $this->automationMonitor = $automationMonitor;
    }

    /**
     * Get all types associated with this provider
     * @return array
     */
    public function getTypes()
    {
        return get_object_vars($this);
    }
}
