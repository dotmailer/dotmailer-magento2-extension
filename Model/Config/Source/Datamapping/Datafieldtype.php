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
            array('value' => 'String', 'label' => 'String'),
            array('value' => 'Numeric', 'label' => 'Numeric'),
            array('value' => 'Date', 'label' => 'Date'),
            array('value' => 'Boolean', 'label' => 'Yes/No')
        ];

        return $dataType;
    }

}