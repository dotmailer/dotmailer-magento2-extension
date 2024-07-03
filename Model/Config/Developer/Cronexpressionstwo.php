<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Cronexpressionstwo implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => 'Every Minute'],
            ['value' => '5', 'label' => 'Every 5 Minutes'],
            ['value' => '10', 'label' => 'Every 10 Minutes'],
            ['value' => '15', 'label' => 'Every 15 Minutes'],
            ['value' => '30', 'label' => 'Every 30 Minutes'],
            ['value' => '00', 'label' => 'Every 60 Minutes'],
        ];
    }
}
