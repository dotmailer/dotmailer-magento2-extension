<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer;

use Dotdigitalgroup\Email\Model\Importer;

class Mode implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Import mode options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Importer::MODE_BULK,
                'label' => Importer::MODE_BULK
            ],
            [
                'value' => Importer::MODE_SINGLE,
                'label' => Importer::MODE_SINGLE
            ],
            [
                'value' => Importer::MODE_SINGLE_DELETE,
                'label' => Importer::MODE_SINGLE_DELETE
            ],
            [
                'value' => Importer::MODE_CONTACT_DELETE,
                'label' => Importer::MODE_CONTACT_DELETE
            ],
            [
                'value' => Importer::MODE_CONTACT_EMAIL_UPDATE,
                'label' => Importer::MODE_CONTACT_EMAIL_UPDATE
            ],
            [
                'value' => Importer::MODE_SUBSCRIBER_UPDATE,
                'label' => Importer::MODE_SUBSCRIBER_UPDATE
            ],
            [
                'value' => Importer::MODE_SUBSCRIBER_UNSUBSCRIBE,
                'label' => Importer::MODE_SUBSCRIBER_UNSUBSCRIBE
            ],
            [
                'value' => Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
                'label' => Importer::MODE_SUBSCRIBER_RESUBSCRIBED
            ]
        ];
    }
}
