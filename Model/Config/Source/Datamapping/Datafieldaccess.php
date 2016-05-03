<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

class Datafieldaccess implements \Magento\Framework\Option\ArrayInterface
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