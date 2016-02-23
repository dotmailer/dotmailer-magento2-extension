<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Frequency
{

    public function toOptionArray()
    {
        return array(
            array('value' => '1', 'label' => '1 Hour'),
            array('value' => '2', 'label' => '2 Hours'),
            array('value' => '6', 'label' => '6 Hours'),
            array('value' => '12', 'label' => '12 Hours'),
            array('value' => '24', 'label' => '24 Hours')
        );
    }
}
