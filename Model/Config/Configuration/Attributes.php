<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Attributes implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $dataHelper;

    /**
     * Attributes constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Returns custom order attributes.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = $this->dataHelper->getOrderTableDescription();

        $customFields[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];
        foreach ($fields as $field) {
            $customFields[] = [
                'value' => $field['COLUMN_NAME'],
                'label' => $field['COLUMN_NAME'],
            ];
        }

        return $customFields;
    }
}
