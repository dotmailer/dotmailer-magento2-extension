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
        if ($cronValue === 'disabled') {
            return '* * 30 2 *'; //Disabled cron will run every 30th of February
        }

        if ($cronValue === '1') {
            return sprintf('*/%s * * * *', $cronValue);
        }

        if ($cronValue !== '00') {
            $valueWithOffset = rand(1, (int)$cronValue - 1) . '-59' . '/' . $cronValue;
            return sprintf('%s * * * *', $valueWithOffset);
        }

        return sprintf('%s * * * *', rand(0, 59));
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
