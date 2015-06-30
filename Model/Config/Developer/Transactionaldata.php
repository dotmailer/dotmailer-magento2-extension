<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Transactionaldata
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '50', 'label' => '50'),
            array('value' => '100', 'label' => '100'),
            array('value' => '200', 'label' => '200'),
            array('value' => '300', 'label' => '300'),
            array('value' => '400', 'label' => '400'),
            array('value' => '500', 'label' => '500'),
        );
    }
}