<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Queue;

use Dotdigitalgroup\Email\Model\Monitor\AbstractStatusProvider;

class StatusProvider extends AbstractStatusProvider
{
    public const QUEUE_STATUS_PROVIDER_EXCEPTION_MESSAGE =
        'Error when reading from flag table - could not identify queue error';

    /**
     * @var string
     */
    protected $statusProviderExceptionMessage = self::QUEUE_STATUS_PROVIDER_EXCEPTION_MESSAGE;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = Monitor::MONITOR_ERROR_FLAG_CODE;

    /**
     * Get the error summary from the flag.
     *
     * @param array $items
     * @return string
     */
    public function getErrorSummary($items = null)
    {
        $summary = [];
        $items = (empty($items)) ? parent::getErrorItemsFromFlag() : $items;
        foreach ($items as $type => $errors) {
            if (empty($errors)) {
                continue;
            }
            $summary[] = $type . ': ' . $errors;
        }
        return implode(', ', $summary);
    }
}
