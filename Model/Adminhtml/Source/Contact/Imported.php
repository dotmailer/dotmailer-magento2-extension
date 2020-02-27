<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact;

class Imported implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '0',
                'label' => 'Not Imported',

            ],
            [
                'value' => '1',
                'label' => 'Imported',
            ]
        ];
    }
}
