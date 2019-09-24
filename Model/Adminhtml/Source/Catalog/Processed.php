<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Catalog;

class Processed implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Catalog processed field options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '0',
                'label' => 'Not processed',

            ],
            [
                'value' => '1',
                'label' => 'Processed',
            ]
        ];
    }
}
