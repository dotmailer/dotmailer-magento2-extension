<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer;

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
            \Dotdigitalgroup\Email\Model\Importer::MODE_BULK =>
                \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE =>
                \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE =>
                \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_DELETE =>
                \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_DELETE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE =>
                \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_UPDATE =>
                \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_UPDATE,
            \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED =>
                \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
        ];
    }

    /**
     * @return void
     */
    public function toOptionArray()
    {

        $options = [
            [
                'value' => \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                'label' => \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
            ],
            [
                'value' => \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                'label' => \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
            ],
            [
                'value' => \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                'label' => \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
            ],
            [
                'value' => \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_DELETE,
                'label' => \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_DELETE,
            ],
            [
                'value' => \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE,
                'label' => \Dotdigitalgroup\Email\Model\Importer::MODE_CONTACT_EMAIL_UPDATE,
            ],
            [
                'value' => \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_UPDATE,
                'label' => \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_UPDATE,
            ],
            [
                'value' => \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
                'label' => \Dotdigitalgroup\Email\Model\Importer::MODE_SUBSCRIBER_RESUBSCRIBED,
            ]
        ];

        return $options;
    }
}
