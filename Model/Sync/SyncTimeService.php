<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use DateInterval;
use DateTime;
use DateTimeZone;

class SyncTimeService
{
    /*
     * @var DateTime
     */
    private $syncFromTime;

    /**
     * Override the time window for abandoned carts sync.
     *
     * e.g. set this to retrieve dropped carts since x time.
     *
     * @param DateTime|null $from
     */
    public function setSyncFromTime(?DateTime $from = null): void
    {
        $this->syncFromTime = $from;
    }

    /**
     * Get the sync from time.
     *
     * @return DateTime|null
     */
    public function getSyncFromTime(): ?DateTime
    {
        return $this->syncFromTime;
    }

    /**
     * Get the more recent end of the sync window.
     *
     * The sync to time is now, minus any configured interval.
     *
     * @param DateInterval|null $subInterval
     * @return DateTime
     */
    public function getSyncToTime(DateInterval $subInterval = null): DateTime
    {
        $toTime = $this->getUTCNowTime();
        if ($subInterval) {
            $toTime->sub($subInterval);
        }

        return $toTime;
    }

    /**
     * @return DateTime
     * @throws \Exception
     */
    public function getUTCNowTime(): DateTime
    {
        return new DateTime('now', new DateTimeZone('UTC'));
    }
}
