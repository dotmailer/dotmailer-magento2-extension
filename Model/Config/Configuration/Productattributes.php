<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Productattributes
{

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $_attributes;

    /**
     * Productattributes constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
    ) {
        $this->_attributes = $collection;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->_attributes
            ->addVisibleFilter();

        $attributeArray = [];
        $attributeArray[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0'
        ];

        foreach ($attributes as $attribute) {
            $attributeArray[] = [
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            ];
        }

        return $attributeArray;
    }
}
