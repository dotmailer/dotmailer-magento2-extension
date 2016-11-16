<?php

namespace Dotdigitalgroup\Email\Model\Config\Dynamic;

class Displaytype implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Display type mode.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'grid', 'label' => 'Grid'],
            ['value' => 'list', 'label' => 'List'],
        ];
    }
}
