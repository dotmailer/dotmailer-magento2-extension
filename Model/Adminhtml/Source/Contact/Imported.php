<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact;

class Imported
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            '1' => __('Imported'),
            'null' => __('Not Imported'),
        ];
    }
}
