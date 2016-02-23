<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Cronexpressionstwo
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '*/15 * * * *', 'label' => 'Every 15 Minutes'),
            array('value' => '*/30 * * * *', 'label' => 'Every 30 Minutes'),
            array('value' => '00 * * * *', 'label' => 'Every 60 Minutes')
        );
    }
}