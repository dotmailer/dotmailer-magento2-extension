<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact;

class Guest implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * To option array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => 'null',
                'label' => ''
            ],
            [
                'value' => '1',
                'label' => 'Guest',
            ]
        ];

        return $options;
    }
}
