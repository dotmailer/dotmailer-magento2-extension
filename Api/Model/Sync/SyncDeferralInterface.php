<?php

namespace Dotdigitalgroup\Email\Api\Model\Sync;

interface SyncDeferralInterface
{
    /**
     * Check if sync should be deferred.
     *
     * @return bool
     */
    public function shouldDeferSync(): bool;
}
