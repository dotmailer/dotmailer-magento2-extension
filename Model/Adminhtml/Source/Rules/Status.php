<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => '0', 'label' => __('Inactive')],
            ['value' => '1', 'label' => __('Active')],
        ];

        return $options;
    }
}
