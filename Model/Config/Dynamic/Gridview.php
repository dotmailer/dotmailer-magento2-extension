<?php

namespace Dotdigitalgroup\Email\Model\Config\Dynamic;

class Gridview
{
    /**
     * grid display options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => '2', 'label' => '2'),
            array('value' => '4', 'label' => '4'),
            array('value' => '6', 'label' => '6'),
            array('value' => '8', 'label' => '8'),
            array('value' => '12', 'label' => '12'),
            array('value' => '16', 'label' => '16'),
            array('value' => '20', 'label' => '20'),
            array('value' => '24', 'label' => '24'),
            array('value' => '28', 'label' => '28'),
            array('value' => '32', 'label' => '32'),
        );
    }

}