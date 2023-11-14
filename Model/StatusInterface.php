<?php

namespace Dotdigitalgroup\Email\Model;

interface StatusInterface
{
    public const SUBSCRIBED = 'Subscribed';
    public const PENDING = 'pending';
    public const PENDING_OPT_IN = 'PendingOptIn';
    public const CONFIRMED = 'Confirmed';
    public const EXPIRED = 'Expired';
    public const SUPPRESSED = 'Suppressed';
    public const CANCELLED = 'Cancelled';
    public const FAILED = 'Failed';
}
