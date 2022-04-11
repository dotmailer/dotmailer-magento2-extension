<?php

namespace Dotdigitalgroup\Email\Model\Cron;

class CronOffsetter
{
    /**
     * Get cron pattern with offset.
     *
     * @param string $cronValue
     * @return string
     */
    public function getCronPatternWithOffset($cronValue)
    {
        if ($cronValue !== '00') {
            $valueWithOffset = rand(1, (int) $cronValue - 1) . '-59' . '/' . $cronValue;
            return sprintf('%s * * * *', $valueWithOffset);
        } else {
            return sprintf('%s * * * *', rand(0, 59));
        }
    }

    /**
     * Get decoded cron value.
     *
     * This function inspects the cron pattern.
     * If "/" character is missing then the cron executed every 60'.
     *
     * @param string $cronValue
     * @return string
     */
    public function getDecodedCronValue($cronValue)
    {
        if (strpos($cronValue, "/") !== false) {
            $temp = explode("/", $cronValue);
            return trim(explode("*", $temp[1])[0]);
        }

        return '00';
    }
}
