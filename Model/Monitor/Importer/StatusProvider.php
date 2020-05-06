<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Importer;

use Dotdigitalgroup\Email\Model\Monitor\AbstractStatusProvider;

class StatusProvider extends AbstractStatusProvider
{
    const IMPORTER_STATUS_PROVIDER_EXCEPTION_MESSAGE =
        'Error when reading from flag table - could not identify importer error job code';

    /**
     * @var string
     */
    protected $statusProviderExceptionMessage = self::IMPORTER_STATUS_PROVIDER_EXCEPTION_MESSAGE;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = Monitor::MONITOR_ERROR_FLAG_CODE;
}
