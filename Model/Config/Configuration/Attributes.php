<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Attributes
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_dataHelper;

    /**
     * Attributes constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $dataHelper
    ) {
        $this->_dataHelper = $dataHelper;
    }

    /**
     * Returns custom order attributes.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = $this->_dataHelper->getOrderTableDescription();

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
