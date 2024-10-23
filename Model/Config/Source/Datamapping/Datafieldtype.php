<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

use Magento\Framework\Data\OptionSourceInterface;

class Datafieldtype implements OptionSourceInterface
{
    /**
     * Datafield model type data mapping.
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
