<?php

namespace Dotdigitalgroup\Email\Model;

interface StatusInterface
{
    const SUBSCRIBED = 'Subscribed';
    const PENDING = 'pending';
    const PENDING_OPT_IN = 'PendingOptIn';
    const CONFIRMED = 'Confirmed';
    const EXPIRED = 'Expired';
    const SUPPRESSED = 'Suppressed';
    const CANCELLED = 'Cancelled';
    const FAILED = 'Failed';
}
