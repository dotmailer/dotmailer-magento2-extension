<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Transactional;

class Port
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '25', 'label' => '25'),
            array('value' => '2525', 'label' => '2525'),
            array('value' => '587', 'label' => '587'),
        );
    }
}