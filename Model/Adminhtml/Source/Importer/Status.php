<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer;

class Status
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            \Dotdigitalgroup\Email\Model\Importer::NOT_IMPORTED => __('Not Imported'),
            \Dotdigitalgroup\Email\Model\Importer::IMPORTING => __('Importing'),
            \Dotdigitalgroup\Email\Model\Importer::IMPORTED => __('Imported'),
            \Dotdigitalgroup\Email\Model\Importer::FAILED => __('Failed'),
        ];
    }
}
