<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Styling
{

    /**
     * Options getter. Styling options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'bold', 'label' => 'Bold'),
            array('value' => 'italic', 'label' => 'Italic'),
            array('value' => 'underline', 'label' => 'Underline')
        );
    }
}