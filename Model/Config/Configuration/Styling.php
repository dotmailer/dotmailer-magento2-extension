<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Styling
{
    /**
     * Options getter. Styling options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'bold', 'label' => 'Bold'],
            ['value' => 'italic', 'label' => 'Italic'],
            ['value' => 'underline', 'label' => 'Underline']
        ];
    }
}
