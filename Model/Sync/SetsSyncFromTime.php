<?php

namespace Dotdigitalgroup\Email\Model\Sync;

trait SetsSyncFromTime
{
    /*
     * @var \DateTime
     */
    private $syncFromTime;

    /**
     * Set a sync from date
     * @param \DateTime|null $from
     * @return $this
     */
    public function setSyncFromTime(\DateTime $from = null)
    {
        $this->syncFromTime = $from;
        return $this;
    }

    /**
     * Get the time to start the sync from
     * @param \DateInterval|null $subInterval   Interval to subtract from the from time
     * @return \DateTime
     * @throws \Exception
     */
    private function getSyncFromTime(\DateInterval $subInterval = null)
    {
        if ($this->syncFromTime) {
            $fromTime = clone $this->syncFromTime;
        } else {
            $fromTime = new \DateTime('now', new \DateTimeZone('UTC'));
            if ($subInterval) {
                $fromTime->sub($subInterval);
            }
        }

        return $fromTime;
    }
}
