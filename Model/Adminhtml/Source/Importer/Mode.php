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
        return [
            \Dotdigitalgroup\Email\Model\Importer::MODE_BULK                    => \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE                  => \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE           => \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_DELETE          => \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_DELETE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE    => \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED => \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
        ];
    }
}
