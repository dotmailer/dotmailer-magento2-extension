<?php

namespace Dotdigitalgroup\Email\Model\Config\Dynamic;

class Gridview implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Grid display options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '2', 'label' => '2'],
            ['value' => '4', 'label' => '4'],
            ['value' => '6', 'label' => '6'],
            ['value' => '8', 'label' => '8'],
            ['value' => '12', 'label' => '12'],
            ['value' => '16', 'label' => '16'],
            ['value' => '20', 'label' => '20'],
            ['value' => '24', 'label' => '24'],
            ['value' => '28', 'label' => '28'],
            ['value' => '32', 'label' => '32'],
        ];
    }
}
