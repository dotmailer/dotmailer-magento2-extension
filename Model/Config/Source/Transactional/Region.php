<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Transactional;

class Region implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => '--- Please Select ---'],
            ['value' => '1', 'label' => 'R1 (Europe) (r1)'],
            ['value' => '2', 'label' => 'R2 (North America) (r2)'],
            ['value' => '3', 'label' => 'R3 (Asia Pacific) (r3)']
        ];
    }
}
