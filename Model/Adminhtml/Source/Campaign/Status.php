<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Campaign;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => '0',
                'label' => 'Pending'
            ],
            [
                'value' => '1',
                'label' => 'Processing',
            ],
            [
                'value' => '2',
                'label' => 'Sent',
            ],
            [
                'value' => '3',
                'label' => 'Failed',
            ]
        ];

        return $options;
    }
}
