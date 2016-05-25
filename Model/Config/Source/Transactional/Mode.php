<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Transactional;

class Mode
{
    /**
     * Options getter.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'smtp', 'label' => 'SMTP']
        ];
    }
}
