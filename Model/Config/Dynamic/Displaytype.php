<?php

namespace Dotdigitalgroup\Email\Model\Config\Dynamic;

class Displaytype
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
            ['value' => 'list', 'label' => 'List']
        ];
    }
}
