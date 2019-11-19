<?php

namespace Dotdigitalgroup\Email\Model\Sync;

interface SyncInterface
{
    /**
     * Run this sync
     * @param \DateTime|null $from   A date to sync from (if supported)
     * @return void
     */
    public function sync(\DateTime $from = null);
}