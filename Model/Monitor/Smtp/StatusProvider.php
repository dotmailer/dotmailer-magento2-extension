<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Smtp;

use Dotdigitalgroup\Email\Model\Monitor\AbstractStatusProvider;

class StatusProvider extends AbstractStatusProvider
{
    const SMTP_STATUS_PROVIDER_EXCEPTION_MESSAGE =
        'Error when reading from flag table - could not identify SMTP error job code';

    /**
     * @var string
     */
    protected $statusProviderExceptionMessage = self::SMTP_STATUS_PROVIDER_EXCEPTION_MESSAGE;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = Monitor::MONITOR_ERROR_FLAG_CODE;
}
