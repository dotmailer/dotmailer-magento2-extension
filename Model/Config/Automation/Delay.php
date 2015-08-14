<?php

namespace Dotdigitalgroup\Email\Model\Config\Automation;

class Delay
{
    /**
     * Returns the values for field delay
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '', 'label' => '-- Please Select --'),
            array('value' => 1, 'label' => '1'),
            array('value' => 2, 'label' => '2'),
            array('value' => 3, 'label' => '3'),
            array('value' => 4, 'label' => '4'),
            array('value' => 5, 'label' => '5'),
            array('value' => 6, 'label' => '6'),
            array('value' => 7, 'label' => '7'),
            array('value' => 14, 'label' => '14'),
            array('value' => 30, 'label' => '30'),
            array('value' => 60, 'label' => '60'),
            array('value' => 90, 'label' => '90')
        );
    }
}