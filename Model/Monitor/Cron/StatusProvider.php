<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Cron;

use Dotdigitalgroup\Email\Model\Monitor\AbstractStatusProvider;

class StatusProvider extends AbstractStatusProvider
{
    const CRON_STATUS_PROVIDER_EXCEPTION_MESSAGE =
        'Error when reading from flag table - could not identify cron error job code';

    /**
     * @var string
     */
    protected $statusProviderExceptionMessage = self::CRON_STATUS_PROVIDER_EXCEPTION_MESSAGE;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = Monitor::MONITOR_ERROR_FLAG_CODE;
}
