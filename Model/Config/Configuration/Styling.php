<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Styling implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options getter. Styling options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => '---- Default Option ----'],
            ['value' => 'bold', 'label' => 'Bold'],
            ['value' => 'italic', 'label' => 'Italic'],
            ['value' => 'underline', 'label' => 'Underline'],
        ];
    }
}
