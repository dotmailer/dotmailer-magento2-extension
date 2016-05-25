<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Frequency
{
    /**
     * Get options.
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => '1 Hour'],
            ['value' => '2', 'label' => '2 Hours'],
            ['value' => '6', 'label' => '6 Hours'],
            ['value' => '12', 'label' => '12 Hours'],
            ['value' => '24', 'label' => '24 Hours'],
        ];
    }
}
