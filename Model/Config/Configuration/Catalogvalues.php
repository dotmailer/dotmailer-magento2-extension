<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogvalues
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => '1',
                'label' => 'Default Level'
            ),
            array(
                'value' => '2',
                'label' => 'Store Level'
            )
        );
    }
}