<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Cronexpressionsone
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '*/5 * * * *', 'label' => 'Every 5 Minutes'),
            array('value' => '*/10 * * * *', 'label' => 'Every 10 Minutes'),
            array('value' => '*/15 * * * *', 'label' => 'Every 15 Minutes')
        );
    }
}