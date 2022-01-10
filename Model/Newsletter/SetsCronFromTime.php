<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

trait SetsCronFromTime
{
    /*
     * @var string An ATOM-format date string.
     */
    private $cronFromTime;

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
     * Use the from time set from the last cron,
     * or default to now minus 24H.
     */
    public function setFromTime()
    {
        if ($this->_getData('fromTime')) {
            $this->cronFromTime = $this->dateTimeFactory->create()->date(
                \DateTime::ATOM,
                $this->_getData('fromTime')
            );
        } else {
            $timezoneNow = $this->timezoneInterfaceFactory->create()
                ->date()
                ->sub(new \DateInterval('PT24H'));

            $this->cronFromTime = $this->dateTimeFactory->create()
                ->date(
                    \DateTime::ATOM,
                    $timezoneNow
                );
        }
    }
}
