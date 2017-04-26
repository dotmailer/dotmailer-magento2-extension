<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules;

class ConditionType implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => '1', 'label' => __('Abandoned Cart Exclusion Rule')],
            ['value' => '2', 'label' => __('Review Email Exclusion Rule')],
        ];

        return $options;
    }
}
