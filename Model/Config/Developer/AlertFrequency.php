<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class AlertFrequency implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Available times.
     *
     * @var array
     */
    private $numbers
        = [
            1,
            12,
            24,
            48,
            72,
        ];

    /**
     * Send to campaign options hours.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = $row = [];

        foreach ($this->numbers as $num) {
            $hourText = $num === 1 ? 'Hour' : 'Hours';
            $row = [
                'value' => $num,
                'label' => $num . ' ' . $hourText,
            ];
            $result[] = $row;
        }

        return $result;
    }
}
