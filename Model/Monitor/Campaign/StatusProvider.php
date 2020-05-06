<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Campaign;

use Dotdigitalgroup\Email\Model\Monitor\AbstractStatusProvider;

class StatusProvider extends AbstractStatusProvider
{
    const CAMPAIGN_STATUS_PROVIDER_EXCEPTION_MESSAGE =
        'Error when reading from flag table - could not identify campaign error job code';

    /**
     * @var string
     */
    protected $statusProviderExceptionMessage = self::CAMPAIGN_STATUS_PROVIDER_EXCEPTION_MESSAGE;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = Monitor::MONITOR_ERROR_FLAG_CODE;
}
