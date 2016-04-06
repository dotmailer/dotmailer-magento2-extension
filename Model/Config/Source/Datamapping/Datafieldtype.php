<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

class Datafieldtype implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Datafield model type.
     * Data mapping.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $dataType = [
            array('value' => 'String', 'label' => __('String')),
            array('value' => 'Numeric', 'label' => __('Numeric')),
            array('value' => 'Date', 'label' => __('Date')),
            array('value' => 'Boolean', 'label' => __('Yes/No'))
        ];

        return $dataType;
    }

}