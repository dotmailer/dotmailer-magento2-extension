<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Cronexpressionsthree implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'disabled', 'label' => 'Disabled'],
            ['value' => '15', 'label' => 'Every 15 Minutes'],
            ['value' => '30', 'label' => 'Every 30 Minutes'],
            ['value' => '00', 'label' => 'Every 60 Minutes'],
        ];
    }
}
