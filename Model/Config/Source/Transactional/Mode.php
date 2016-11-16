<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Transactional;

class Mode implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'smtp', 'label' => 'SMTP'],
        ];
    }
}
