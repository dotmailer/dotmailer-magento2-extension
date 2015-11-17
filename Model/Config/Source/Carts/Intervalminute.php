<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Carts;

class Intervalminute implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * lost basket hour options
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => "15", 'label' => '15 Minutes'),
            array('value' => "20", 'label' => '20 Minutes'),
            array('value' => "25", 'label' => '25 Minutes'),
            array('value' => "30", 'label' => '30 Minutes'),
            array('value' => "40", 'label' => '40 Minutes'),
            array('value' => "50", 'label' => '50 Minutes'),
            array('value' => "60", 'label' => '60 Minutes'),
        );
    }
}