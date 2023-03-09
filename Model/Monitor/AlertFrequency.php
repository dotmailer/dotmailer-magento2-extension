<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Helper\Config;

class AlertFrequency
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * AlertFrequency constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Set time window for system alerts.
     *
     * @return array
     * @throws \Exception
     */
    public function setTimeWindow()
    {
        $toTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $hours = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_FREQUENCY
        );

        $interval = new \DateInterval(sprintf('PT%sH', $hours));
        $fromTime = clone $toTime;
        $fromTime->sub($interval);

        return [
            'from' => $fromTime->format('Y-m-d H:i:s'),
            'to' => $toTime->format('Y-m-d H:i:s'),
            'date' => true,
        ];
    }
}
