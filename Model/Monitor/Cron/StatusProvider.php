<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Cron;

use Dotdigitalgroup\Email\Model\Monitor\Cron\Monitor;
use Magento\Framework\FlagManager;

class StatusProvider
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * StatusProvider constructor.
     * @param FlagManager $flagManager
     */
    public function __construct(
        FlagManager $flagManager
    ) {
        $this->flagManager = $flagManager;
    }

    /**
     * @return array|null
     */
    public function getErrors()
    {
        return $this->flagManager->getFlagData(Monitor::CRON_MONITOR_ERROR_FLAG_CODE);
    }
}
