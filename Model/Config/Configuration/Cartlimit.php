<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Cartlimit implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Available times.
     *
     * @var array
     */
    private $times
        = [
            0,
            1,
            2,
            3,
            4,
            5,
            6,
            12,
            24,
            36,
            48,
            60,
            72,
            84,
            96,
            108,
            120,
            240,
        ];

    /**
     * Send to campain options hours.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = $row = [];
        $i = 0;
        foreach ($this->times as $one) {
            if ($i == 0) {
                $row = ['value' => $one, 'label' => 'No Limit'];
            } elseif ($i == 1) {
                $row = [
                    'value' => $one,
                    'label' => $one . ' Hour',
                ];
            } else {
                $row = [
                    'value' => $one,
                    'label' => $one . ' Hours',
                ];
            }
            $result[] = $row;
            ++$i;
        }

        return $result;
    }
}
