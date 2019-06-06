<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer;

use Dotdigitalgroup\Email\Model\Importer;

class Mode implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            Importer::MODE_BULK =>
                Importer::MODE_BULK,
            Importer::MODE_SINGLE =>
                Importer::MODE_SINGLE,
            Importer::MODE_SINGLE_DELETE =>
                Importer::MODE_SINGLE_DELETE,
            Importer::MODE_CONTACT_DELETE =>
                Importer::MODE_CONTACT_DELETE,
            Importer::MODE_CONTACT_EMAIL_UPDATE =>
                Importer::MODE_CONTACT_EMAIL_UPDATE,
            Importer::MODE_SUBSCRIBER_UPDATE =>
                Importer::MODE_SUBSCRIBER_UPDATE,
            Importer::MODE_SUBSCRIBER_RESUBSCRIBED =>
                Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
        ];
    }

    /**
     * @return void
     */
    public function toOptionArray()
    {

        $options = [
            [
                'value' => Importer::MODE_BULK,
                'label' => Importer::MODE_BULK,
            ],
            [
                'value' => Importer::MODE_SINGLE,
                'label' => Importer::MODE_SINGLE,
            ],
            [
                'value' => Importer::MODE_SINGLE_DELETE,
                'label' => Importer::MODE_SINGLE_DELETE,
            ],
            [
                'value' => Importer::MODE_CONTACT_DELETE,
                'label' => Importer::MODE_CONTACT_DELETE,
            ],
            [
                'value' => Importer::MODE_CONTACT_EMAIL_UPDATE,
                'label' => Importer::MODE_CONTACT_EMAIL_UPDATE,
            ],
            [
                'value' => Importer::MODE_SUBSCRIBER_UPDATE,
                'label' => Importer::MODE_SUBSCRIBER_UPDATE,
            ],
            [
                'value' => Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
                'label' => Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
            ]
        ];

        return $options;
    }
}
