<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact;

class Modified
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            '1' => __('Modified'),
            'null' => __('Not Modified'),
        ];
    }
}
