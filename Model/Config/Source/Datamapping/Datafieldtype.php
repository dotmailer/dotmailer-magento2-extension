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
            ['value' => 'String', 'label' => __('String')],
            ['value' => 'Numeric', 'label' => __('Numeric')],
            ['value' => 'Date', 'label' => __('Date')],
            ['value' => 'Boolean', 'label' => __('Yes/No')],
        ];

        return $dataType;
    }
}
