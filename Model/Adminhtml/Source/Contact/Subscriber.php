<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact;

class Subscriber implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
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
                'label' => 'Subscriber',
            ]
        ];

        return $options;
    }
}
