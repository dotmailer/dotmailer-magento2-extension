<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Transactional;

class Port implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '25', 'label' => '25'],
            ['value' => '2525', 'label' => '2525'],
            ['value' => '587', 'label' => '587'],
        ];
    }
}
