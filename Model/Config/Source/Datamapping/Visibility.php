<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

class Visibility
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $dataType = array(
            array('value' => 'Private', 'label' => __('Private')),
            array('value' => 'Public', 'label' => __('Public')),
        );

        return $dataType;
    }
}