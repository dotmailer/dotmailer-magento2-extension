<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Carts;

class Intervalminute implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Lost basket hour options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '15', 'label' => __('15 Minutes')],
            ['value' => '20', 'label' => __('20 Minutes')],
            ['value' => '25', 'label' => __('25 Minutes')],
            ['value' => '30', 'label' => __('30 Minutes')],
            ['value' => '40', 'label' => __('40 Minutes')],
            ['value' => '50', 'label' => __('50 Minutes')],
            ['value' => '60', 'label' => __('60 Minutes')],
        ];
    }
}
