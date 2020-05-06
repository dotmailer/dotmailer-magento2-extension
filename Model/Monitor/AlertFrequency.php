<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;
use Dotdigitalgroup\Email\Helper\Config;

class AlertFrequency
{
    /**
     * @var DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * AlertFrequency constructor.
     *
     * @param DateIntervalFactory $dateIntervalFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        DateIntervalFactory $dateIntervalFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Set time window for system alerts.
     *
     * @param \DateTime $syncFromTime
     * @return array
     */
    public function setTimeWindow(\DateTime $syncFromTime)
    {
        $toTime = $syncFromTime;

        $hours = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_FREQUENCY
        );

        $interval = $this->dateIntervalFactory->create(
            ['interval_spec' => sprintf('PT%sH', $hours)]
        );

        $fromTime = clone $toTime;
        $fromTime->sub($interval);

        return [
            'from' => $fromTime->format('Y-m-d H:i:s'),
            'to' => $toTime->format('Y-m-d H:i:s'),
            'date' => true,
        ];
    }
}
