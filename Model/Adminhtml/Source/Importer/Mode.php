<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer;

class Mode
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array(
	        \Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK           => \Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
	        \Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE         => \Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE,
	        \Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE  => \Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
	        \Dotdigitalgroup\Email\Model\Proccessor::MODE_CONTACT_DELETE => \Dotdigitalgroup\Email\Model\Proccessor::MODE_CONTACT_DELETE
        );
    }
}