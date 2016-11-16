<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Fontsize implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options getter. Styling options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '8px', 'label' => '8px'],
            ['value' => '9px', 'label' => '9px'],
            ['value' => '10px', 'label' => '10px'],
            ['value' => '11px', 'label' => '11px'],
            ['value' => '12px', 'label' => '12px'],
            ['value' => '13px', 'label' => '13px'],
            ['value' => '14px', 'label' => '14px'],
            ['value' => '15px', 'label' => '15px'],
            ['value' => '16px', 'label' => '16px'],
            ['value' => '17px', 'label' => '17px'],
            ['value' => '18px', 'label' => '18px'],
            ['value' => '19px', 'label' => '19px'],
            ['value' => '20px', 'label' => '20px'],
            ['value' => '21px', 'label' => '21px'],
            ['value' => '22px', 'label' => '22px'],
        ];
    }
}
