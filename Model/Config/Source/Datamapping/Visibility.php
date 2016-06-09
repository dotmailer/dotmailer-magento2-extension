<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

class Visibility
{
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $dataType = [
            ['value' => 'Private', 'label' => __('Private')],
            ['value' => 'Public', 'label' => __('Public')],
        ];

        return $dataType;
    }
}
