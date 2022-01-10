<?php

namespace Dotdigitalgroup\Email\Model\Cron;

class CronOffsetter
{
    /**
     * @param $cronValue
     * @return string
     */
    public function getCronPatternWithOffset($cronValue)
    {
        if ($cronValue !== '00') {
            $valueWithOffset = rand(1, $cronValue -1) . '-59' . '/' . $cronValue;
            return sprintf('%s * * * *', $valueWithOffset);
        } else {
            return sprintf('%s * * * *', (string) rand(0, 59));
        }
    }

    /**
     * @param $cronValue
     * @return mixed|string
     */
    public function getDecodedCronValue($cronValue)
    {
        $temp = explode("/", $cronValue);
        return explode("*", $temp[1])[0];
    }
}
