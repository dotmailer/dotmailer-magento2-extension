<?php

namespace Dotdigitalgroup\Email\Model\Cron;

use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;

class CronFromTimeSetter
{
    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var TimezoneInterfaceFactory
     */
    private $timezoneInterfaceFactory;

    /**
     * @var string An ATOM-format date string.
     */
    private $cronFromTime;

    /**
     * @param DateTimeFactory $dateTimeFactory
     * @param TimezoneInterfaceFactory $timezoneInterfaceFactory
     */
    public function __construct(
        DateTimeFactory $dateTimeFactory,
        TimezoneInterfaceFactory $timezoneInterfaceFactory
    ) {
        $this->dateTimeFactory = $dateTimeFactory;
        $this->timezoneInterfaceFactory = $timezoneInterfaceFactory;
    }

    /**
     * Get the stored time, or set it and return it.
     *
     * @return string
     */
    public function getFromTime()
    {
        if ($this->cronFromTime) {
            return $this->cronFromTime;
        }
        $this->setFromTime();
        return $this->cronFromTime;
    }

    /**
     * Set from time.
     *
     * Use the from time set from the last cron,
     * or default to now minus 24H.
     *
     * @param string $fromTime
     */
    public function setFromTime(string $fromTime = '')
    {
        if ($fromTime) {
            $this->cronFromTime = $this->dateTimeFactory->create()->date(
                \DateTimeInterface::ATOM,
                $fromTime
            );
        } else {
            $timezoneNow = $this->timezoneInterfaceFactory->create()
                ->date()
                ->sub(new \DateInterval('PT24H'));

            $this->cronFromTime = $this->dateTimeFactory->create()
                ->date(
                    \DateTimeInterface::ATOM,
                    $timezoneNow
                );
        }
    }
}
