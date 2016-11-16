<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogvalues implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '1',
                'label' => 'Default Level',
            ],
            [
                'value' => '2',
                'label' => 'Store Level',
            ],
        ];
    }
}
