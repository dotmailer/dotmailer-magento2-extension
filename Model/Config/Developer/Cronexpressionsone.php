<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Cronexpressionsone implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '*/5 * * * *', 'label' => 'Every 5 Minutes'],
            ['value' => '*/10 * * * *', 'label' => 'Every 10 Minutes'],
            ['value' => '*/15 * * * *', 'label' => 'Every 15 Minutes'],
        ];
    }
}
