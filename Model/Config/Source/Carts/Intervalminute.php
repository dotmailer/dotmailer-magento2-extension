<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Carts;

class Intervalminute implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * lost basket hour options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => "15", 'label' => __('15 Minutes')),
            array('value' => "20", 'label' => __('20 Minutes')),
            array('value' => "25", 'label' => __('25 Minutes')),
            array('value' => "30", 'label' => __('30 Minutes')),
            array('value' => "40", 'label' => __('40 Minutes')),
            array('value' => "50", 'label' => __('50 Minutes')),
            array('value' => "60", 'label' => __('60 Minutes')),
        );
    }
}