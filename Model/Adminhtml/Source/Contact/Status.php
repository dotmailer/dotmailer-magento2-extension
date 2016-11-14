<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => '1',
                'label' => 'Subscribed'
            ],
            [
                'value' => '2',
                'label' => 'Not Active',
            ],
            [
                'value' => '3',
                'label' => 'Unsubscribed',
            ],
            [
                'value' => '4',
                'label' => 'Unconfirmed',
            ]
        ];

        return $options;
    }
}
