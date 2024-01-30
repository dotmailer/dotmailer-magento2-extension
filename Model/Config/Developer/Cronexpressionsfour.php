<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Cronexpressionsfour implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '30', 'label' => 'Every 30 Days'],
            ['value' => '14', 'label' => 'Every 14 Days'],
            ['value' => '7', 'label' => 'Every 7 Days'],
            ['value' => '1', 'label' => 'Every Day'],
        ];
    }
}
