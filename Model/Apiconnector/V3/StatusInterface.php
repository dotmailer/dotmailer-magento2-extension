<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector\V3;

interface StatusInterface
{
    public const PENDING_OPT_IN = 'pendingOptIn';
    public const SUPPRESSED = 'suppressed';
    public const SUBSCRIBED = 'subscribed';
    public const UNSUBSCRIBED = 'unsubscribed';
}
