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
        return array(
            \Dotdigitalgroup\Email\Model\Proccessor::NOT_IMPORTED => __('Not Imported'),
            \Dotdigitalgroup\Email\Model\Proccessor::IMPORTING    => __('Importing'),
            \Dotdigitalgroup\Email\Model\Proccessor::IMPORTED     => __('Imported'),
            \Dotdigitalgroup\Email\Model\Proccessor::FAILED       => __('Failed'),
        );
    }
}