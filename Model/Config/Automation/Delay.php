<?php

namespace Dotdigitalgroup\Email\Model\Config\Automation;

class Delay implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Returns the values for field delay.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => '-- Please Select --'],
            ['value' => 1, 'label' => '1'],
            ['value' => 2, 'label' => '2'],
            ['value' => 3, 'label' => '3'],
            ['value' => 4, 'label' => '4'],
            ['value' => 5, 'label' => '5'],
            ['value' => 6, 'label' => '6'],
            ['value' => 7, 'label' => '7'],
            ['value' => 14, 'label' => '14'],
            ['value' => 30, 'label' => '30'],
            ['value' => 60, 'label' => '60'],
            ['value' => 90, 'label' => '90'],
        ];
    }
}
