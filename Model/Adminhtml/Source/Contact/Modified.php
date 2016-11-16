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
                'label' => '',

            ],
            [
                'value' => '1',
                'label' => 'Modified',
            ]
        ];
    }
}
