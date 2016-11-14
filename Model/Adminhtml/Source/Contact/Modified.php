<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact;

class Modified implements \Magento\Framework\Data\OptionSourceInterface
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
                'value' => 'null',
                'label' => 'Not Modified',

            ],
            [
                'value' => '1',
                'label' => 'Modified',
            ]
        ];
    }
}
