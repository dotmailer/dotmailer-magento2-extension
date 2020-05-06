<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Automation;

use Dotdigitalgroup\Email\Model\Monitor\AbstractStatusProvider;

class StatusProvider extends AbstractStatusProvider
{
    const AUTOMATION_STATUS_PROVIDER_EXCEPTION_MESSAGE =
        'Error when reading from flag table - could not identify automation error job code';

    /**
     * @var string
     */
    protected $statusProviderExceptionMessage = self::AUTOMATION_STATUS_PROVIDER_EXCEPTION_MESSAGE;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = Monitor::MONITOR_ERROR_FLAG_CODE;
}
