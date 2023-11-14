<?php

namespace Dotdigitalgroup\Email\Model;

class DateTime extends \DateTime
{
    /**
     * @var DateTimeZoneFactory
     */
    private $dateTimeZoneFactory;

    /**
     * DateTime constructor.
     *
     * @param DateTimeZoneFactory $dateTimeZoneFactory
     * @param string $time
     * @param DateTimeZone|null $timezone
     * @throws \Exception
     */
    public function __construct(
        DateTimeZoneFactory $dateTimeZoneFactory,
        $time = 'now',
        DateTimeZone $timezone = null
    ) {
        $this->dateTimeZoneFactory = $dateTimeZoneFactory;
        parent::__construct($time, $timezone);
    }

    /**
     * Get UTC date.
     *
     * @return DateTime
     */
    public function getUtcDate()
    {
        return $this->setTimezone($this->dateTimeZoneFactory->create(['timezone' => 'UTC']));
    }
}
