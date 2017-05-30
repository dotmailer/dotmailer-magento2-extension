<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer;

class Status implements \Magento\Framework\Data\OptionSourceInterface
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

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => '0',
                'label' => 'Not Imported',
            ],
            [
                'value' => '1',
                'label' => 'Importing',
            ],
            [
                'value' => '2',
                'label' => 'Imported',
            ],
            [
                'value' => '3',
                'label' => 'Failed',
            ]
        ];

        return $options;
    }
}
