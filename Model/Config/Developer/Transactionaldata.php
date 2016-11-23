<?php

namespace Dotdigitalgroup\Email\Model\Config\Developer;

class Transactionaldata implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '50', 'label' => '50'],
            ['value' => '100', 'label' => '100'],
            ['value' => '200', 'label' => '200'],
            ['value' => '300', 'label' => '300'],
            ['value' => '400', 'label' => '400'],
            ['value' => '500', 'label' => '500'],
        ];
    }
}
